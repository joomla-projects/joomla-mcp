<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  WebService.Internal
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\CMS\WebService\Internal;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\User\User;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\CMS\WebService\Operation\OperationInput;
use Tobscure\JsonApi\Exception\InvalidParameterException;

/**
 * Dispatches a compiled operation directly to its existing Joomla component controller and task.
 *
 * No route is parsed and no network request is made. The component is booted through Joomla's extension manager, and
 * its own dispatcher and MVC factory are used. This preserves existing controllers and third-party component service
 * providers while keeping the internal request and response isolated from the outer MCP request.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ComponentApiDispatcher implements InternalApiDispatcherInterface
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(private readonly CMSApplication $application)
    {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        OperationDefinition $operation,
        OperationInput $input,
        User $identity,
    ): InternalApiResponse {
        $parent = $this->application;

        if (!$parent->isClient('api')) {
            throw new \RuntimeException('Internal API dispatch requires the Joomla API application.');
        }

        $component = $operation->acl['component'] ?? null;

        if (!\is_string($component) || $component === '') {
            throw new \LogicException(
                \sprintf('Operation %s does not define a Joomla component.', $operation->operationId),
            );
        }

        $query  = $this->expandQueryParameters($input->query);
        $source = array_replace_recursive(
            $operation->routeDefaults,
            $input->body,
            $query,
            $input->path,
        );
        $source['data']       = $input->body;
        $source['option']     = $component;
        $source['controller'] = $operation->controller;
        $source['task']       = $operation->controller . '.' . $operation->task;
        $source['format']     = 'jsonapi';

        $requestInput = new InternalApiInput($source, $input->body, $query, $operation->method);
        $application  = new InternalApiApplication($parent, $requestInput, $identity);
        $factoryState = $this->replaceFactoryState($application);
        $outputLevel  = ob_get_level();
        $exception    = null;

        ob_start();

        try {
            $extension  = $application->bootComponent($component);
            $dispatcher = $extension->getDispatcher($application, $requestInput);
            $dispatcher->dispatch();
        } catch (InternalApiApplicationClosed) {
            // The controller has deliberately completed the internal response.
        } catch (\Throwable $caught) {
            $exception = $caught;
        } finally {
            $output = $this->cleanOutputBuffer($outputLevel);
            $this->restoreFactoryState($factoryState);
        }

        if ($exception !== null) {
            return $this->exceptionResponse($exception, $parent);
        }

        $headers    = $this->normaliseHeaders($application);
        $statusCode = $this->statusCode($headers, $operation->successStatus);
        $mediaType  = $this->mediaType($headers);
        $body       = $this->responseBody($application, $output, $statusCode);

        return new InternalApiResponse($statusCode, $body, $headers, $mediaType);
    }

    /**
     * @param  array<string, mixed>  $parameters  Flat query parameters.
     *
     * @return  array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function expandQueryParameters(array $parameters): array
    {
        $query = [];

        foreach ($parameters as $name => $value) {
            if (preg_match('/^([^\[]+)((?:\[[^\]]+\])+)$/', $name, $matches) !== 1) {
                $query[$name] = $value;
                continue;
            }

            preg_match_all('/\[([^\]]+)\]/', $matches[2], $segments);
            $cursor = &$query[$matches[1]];

            foreach ($segments[1] as $index => $segment) {
                if ($index === array_key_last($segments[1])) {
                    $cursor[$segment] = $value;
                    continue;
                }

                $cursor[$segment] ??= [];
                $cursor = &$cursor[$segment];
            }

            unset($cursor);
        }

        return $query;
    }

    /**
     * @return  array{application: mixed, document: mixed}
     *
     * @since  __DEPLOY_VERSION__
     */
    private function replaceFactoryState(InternalApiApplication $application): array
    {
        $state = [
            'application' => self::readFactoryProperty('application'),
            'document'    => self::readFactoryProperty('document'),
        ];

        self::writeFactoryProperty('application', $application);
        self::writeFactoryProperty('document', $application->getDocument());

        return $state;
    }

    /**
     * @param  array{application: mixed, document: mixed}  $state  Previous factory state.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function restoreFactoryState(array $state): void
    {
        self::writeFactoryProperty('application', $state['application']);
        self::writeFactoryProperty('document', $state['document']);
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private static function readFactoryProperty(string $name): mixed
    {
        if (!property_exists(Factory::class, $name)) {
            return null;
        }

        return (new \ReflectionProperty(Factory::class, $name))->getValue();
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private static function writeFactoryProperty(string $name, mixed $value): void
    {
        if (!property_exists(Factory::class, $name)) {
            return;
        }

        (new \ReflectionProperty(Factory::class, $name))->setValue(null, $value);
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function cleanOutputBuffer(int $startingLevel): string
    {
        $output = '';

        while (ob_get_level() > $startingLevel) {
            $output = (string) ob_get_clean() . $output;
        }

        return trim($output);
    }

    /**
     * @return  array<string, list<string>>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function normaliseHeaders(InternalApiApplication $application): array
    {
        $normalised = [];

        if (!method_exists($application, 'getHeaders')) {
            return $normalised;
        }

        foreach ($application->getHeaders() as $header) {
            $name  = \is_array($header) ? ($header['name'] ?? null) : ($header->name ?? null);
            $value = \is_array($header) ? ($header['value'] ?? null) : ($header->value ?? null);

            if (!\is_string($name) || (!\is_scalar($value) && $value !== null)) {
                continue;
            }

            $normalised[$name][] = (string) $value;
        }

        return $normalised;
    }

    /**
     * @param  array<string, list<string>>  $headers  Response headers.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function statusCode(array $headers, int $default): int
    {
        foreach ($headers as $name => $values) {
            if (strtolower($name) !== 'status' || $values === []) {
                continue;
            }

            if (preg_match('/\d{3}/', $values[array_key_last($values)], $matches) === 1) {
                return (int) $matches[0];
            }
        }

        return $default;
    }

    /**
     * @param  array<string, list<string>>  $headers  Response headers.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function mediaType(array $headers): string
    {
        foreach ($headers as $name => $values) {
            if (strtolower($name) !== 'content-type' || $values === []) {
                continue;
            }

            return trim(explode(';', $values[array_key_last($values)], 2)[0]);
        }

        return 'application/vnd.api+json';
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function responseBody(InternalApiApplication $application, string $output, int $statusCode): mixed
    {
        if ($output !== '') {
            return $this->decodeJson($output);
        }

        if ($statusCode === 204) {
            return null;
        }

        $document = $application->getDocument();

        if (method_exists($document, 'toArray')) {
            $body = $document->toArray();

            return $body === [] ? null : $body;
        }

        $rendered = (string) $document;

        return $rendered === '' ? null : $this->decodeJson($rendered);
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function decodeJson(string $body): mixed
    {
        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $body;
        }
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function exceptionResponse(\Throwable $exception, CMSApplication $parent): InternalApiResponse
    {
        $status = $this->exceptionStatus($exception);
        $detail = $status >= 500 && !(bool) $parent->get('debug')
            ? 'The internal Joomla API request failed.'
            : $exception->getMessage();

        return new InternalApiResponse(
            $status,
            [
                'errors' => [
                    [
                        'status' => (string) $status,
                        'title'  => $this->statusTitle($status),
                        'detail' => $detail,
                    ],
                ],
            ],
        );
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function exceptionStatus(\Throwable $exception): int
    {
        $knownStatus = match (true) {
            $exception instanceof NotAllowed                => 403,
            $exception instanceof ResourceNotFound          => 404,
            $exception instanceof InvalidParameterException => 400,
            default                                         => null,
        };

        if ($knownStatus !== null) {
            return $knownStatus;
        }

        $code = (int) $exception->getCode();

        return $code >= 400 && $code <= 599 ? $code : 500;
    }

    /**
     * @since  __DEPLOY_VERSION__
     */
    private function statusTitle(int $status): string
    {
        return match ($status) {
            400     => 'Bad Request',
            401     => 'Unauthorised',
            403     => 'Forbidden',
            404     => 'Not Found',
            409     => 'Conflict',
            422     => 'Unprocessable Content',
            default => $status >= 500 ? 'Internal Server Error' : 'API Error',
        };
    }
}
