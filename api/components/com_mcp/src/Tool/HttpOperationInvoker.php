<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Tool;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\MCP\Api\Core\McpRequestContext;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Executes canonical operations through the site's established Joomla REST API.
 *
 * This implementation deliberately uses the public REST boundary for the first exploratory integration. It therefore
 * exercises the existing router, controller, validation and authorisation behaviour rather than duplicating them in
 * the MCP layer. A later internal dispatcher can replace this class without changing tool definitions.
 *
 * @since  __DEPLOY_VERSION__
 */
final class HttpOperationInvoker implements OperationInvokerInterface
{
    private readonly string $baseUri;
    private readonly \Closure $tokenProvider;
    private readonly \Closure $requester;

    /**
     * @param callable(): ?string|null $tokenProvider
     * @param callable(string, string, array<string, string>, ?string, int): OperationResult|null $requester
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly OperationArgumentMapper $argumentMapper = new OperationArgumentMapper(),
        ?string $baseUri = null,
        ?callable $tokenProvider = null,
        ?callable $requester = null,
        private readonly int $timeout = 30,
    ) {
        $this->baseUri = rtrim($baseUri ?? Uri::root() . 'api/index.php/', '/') . '/';
        $this->tokenProvider = $tokenProvider === null
            ? $this->extractBearerToken(...)
            : \Closure::fromCallable($tokenProvider);
        $this->requester = $requester === null
            ? $this->sendRequest(...)
            : \Closure::fromCallable($requester);
    }

    public function invoke(OperationDefinition $operation, array $arguments): OperationResult
    {
        $input = $this->argumentMapper->map($operation, $arguments);
        $path = $operation->path;

        foreach ($input->path as $name => $value) {
            $path = str_replace(':' . $name, rawurlencode((string) $value), $path);
        }

        if (preg_match('/:[A-Za-z_][A-Za-z0-9_]*/', $path, $matches) === 1) {
            throw new \InvalidArgumentException(
                sprintf('The required path argument %s was not supplied.', ltrim($matches[0], ':')),
            );
        }

        $url = $this->baseUri . ltrim($path, '/');

        if ($input->query !== []) {
            $url .= '?' . http_build_query($input->query, '', '&', PHP_QUERY_RFC3986);
        }

        $token = ($this->tokenProvider)();

        if (!\is_string($token) || trim($token) === '') {
            throw new \RuntimeException(
                'The authenticated MCP request token is not available to the web service invoker.',
            );
        }

        $headers = [
            'Accept' => 'application/vnd.api+json',
            'X-Joomla-Token' => trim($token),
        ];
        $body = null;

        if ($input->body !== []) {
            $body = json_encode(
                $input->body,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
            $headers['Content-Type'] = 'application/json';
        }

        $result = ($this->requester)($operation->method, $url, $headers, $body, $this->timeout);

        return new OperationResult(
            $result->statusCode,
            $this->normaliseResponseBody($result->body),
            $result->mediaType,
        );
    }

    private function extractBearerToken(): ?string
    {
        $contextToken = McpRequestContext::getToken();

        if ($contextToken !== null && $contextToken !== '') {
            return $contextToken;
        }

        $candidates = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        foreach (['getallheaders', 'apache_request_headers'] as $function) {
            if (!\function_exists($function)) {
                continue;
            }

            $headers = $function();

            if (\is_array($headers)) {
                $candidates[] = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            }
        }

        foreach ($candidates as $candidate) {
            if (\is_string($candidate) && preg_match('/^Bearer\s+(.+)$/i', trim($candidate), $matches) === 1) {
                return trim($matches[1]);
            }
        }

        $queryToken = $_GET['token'] ?? null;

        return \is_string($queryToken) && $queryToken !== '' ? $queryToken : null;
    }

    /**
     * @param array<string, string> $headers
     */
    private function sendRequest(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout,
    ): OperationResult {
        if (!\function_exists('curl_init')) {
            throw new \RuntimeException(
                'The PHP cURL extension is required by the exploratory HTTP operation invoker.',
            );
        }

        $handle = curl_init($url);

        if ($handle === false) {
            throw new \RuntimeException('The web service request could not be initialised.');
        }

        $headerLines = [];

        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array(
            $handle,
            [
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headerLines,
            ],
        );

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $error = curl_error($handle);
            curl_close($handle);

            $message = $error === ''
                ? 'The Joomla web service request failed.'
                : 'The Joomla web service request failed: ' . $error;

            throw new \RuntimeException($message);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);

        $mediaType = trim(explode(';', $contentType, 2)[0]) ?: 'application/octet-stream';

        return new OperationResult(
            $statusCode,
            $this->decodeResponseBody($responseBody, $mediaType),
            $mediaType,
        );
    }

    private function decodeResponseBody(string $body, string $mediaType): mixed
    {
        if ($body === '') {
            return null;
        }

        if (!str_contains(strtolower($mediaType), 'json') && !\in_array($body[0], ['{', '['], true)) {
            return $body;
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $body;
        }
    }

    private function normaliseResponseBody(mixed $body): mixed
    {
        if (!\is_array($body) || !array_key_exists('data', $body)) {
            return $body;
        }

        return $this->normaliseJsonApiData($body['data']);
    }

    private function normaliseJsonApiData(mixed $data): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map($this->normaliseJsonApiResource(...), $data);
        }

        return $this->normaliseJsonApiResource($data);
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return array<string, mixed>
     */
    private function normaliseJsonApiResource(array $resource): array
    {
        $normalised = \is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];

        if (array_key_exists('id', $resource) && !array_key_exists('id', $normalised)) {
            $normalised['id'] = ctype_digit((string) $resource['id'])
                ? (int) $resource['id']
                : $resource['id'];
        }

        return $normalised;
    }
}
