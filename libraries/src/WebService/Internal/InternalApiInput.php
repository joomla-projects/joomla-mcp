<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Internal;

use Joomla\Input\Input;
use Joomla\Input\Json;

/**
 * Isolated JSON input for an internal API subrequest.
 *
 * The object mirrors the request views available to existing API controllers. This includes the decoded JSON body,
 * the raw JSON payload, the request method and the usual get, post, request, server and json input objects.
 *
 * @since  __DEPLOY_VERSION__
 */
final class InternalApiInput extends Json
{
    private string $rawBody;
    private string $requestMethod;

    /**
     * @param array<string, mixed> $source
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @param array<string, mixed> $server
     * @param array<string, mixed> $options
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        array $source,
        array $body,
        array $query,
        string $method,
        array $server = [],
        array $options = [],
    ) {
        parent::__construct($source, $options);

        $this->requestMethod = strtoupper($method);
        $this->rawBody       = $body === []
            ? ''
            : json_encode(
                $body,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );

        $server = array_replace(
            $_SERVER,
            [
                'REQUEST_METHOD' => $this->requestMethod,
                'CONTENT_TYPE'   => $body === [] ? null : 'application/json',
                'HTTP_ACCEPT'    => 'application/vnd.api+json',
            ],
            $server,
        );

        $post = $this->requestMethod === 'POST' ? $body : [];

        // Existing Joomla and third-party controllers may use any of these request views.
        $this->inputs['json']    = $this;
        $this->inputs['get']     = new Input($query, $options);
        $this->inputs['post']    = new Input($post, $options);
        $this->inputs['request'] = new Input($source, $options);
        $server                  = array_filter($server, static fn ($value): bool => $value !== null);
        $this->inputs['server']  = new Input($server, $options);
    }

    /**
     * Returns the isolated JSON body rather than the outer MCP request body.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getRaw()
    {
        return $this->rawBody;
    }

    /**
     * Returns the internal request method without consulting global server state.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }
}
