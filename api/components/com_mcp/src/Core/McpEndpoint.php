<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\User\CurrentUserTrait;
use Joomla\CMS\User\User;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use Mcp\Server\HttpServerRunner;
use Mcp\Server\Server;
use Mcp\Server\Transport\Http\BufferedIo;
use Mcp\Server\Transport\Http\FileSessionStore;
use Mcp\Server\Transport\Http\HttpMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * MCP HTTP Endpoint for remote access
 *
 * @since  __DEPLOY_VERSION__
 */
class McpEndpoint
{
    use CurrentUserTrait;

    /**
     * @since __DEPLOY_VERSION__
     */
    private LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param AbilityRegistry $abilityRegistry Tool registry
     * @param array           $config          Configuration. Possible keys:
     *                        - logger: Logger instance, defaults to NullLogger
     *                        - server_name: Server name, defaults to 'Joomla MCP Server'
     *                        - session_timeout: Session timeout in seconds, defaults to 1800
     *                        - max_queue_size: Maximum queue size, defaults to 500
     *                        - enable_sse: Enable Server-Sent Events, defaults to false
     *                        - shared_hosting: Enable shared hosting mode, defaults to false
     *                        - tmp_dir: Temporary directory, defaults to JPATH_ROOT . '/tmp'
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly AbilityRegistry $abilityRegistry,
        private readonly AuthServiceInterface $authService,
        private readonly array $config = []
    ) {
        $this->logger = $this->config['logger'] ?? new NullLogger();
    }

    /**
     * Invoke the endpoint
     *
     * @param HttpMessage $request
     * @return ResponseInterface
     * @since  __DEPLOY_VERSION__
     */
    public function handle(HttpMessage $request): ResponseInterface
    {
        try {
            $headers     = $request->getHeaders();
            $queryParams = $request->getQueryParams();

            $this->logger->debug("MCP: Request method: " . $request->getMethod());
            $this->logger->debug("MCP: Request headers: " . json_encode($headers));
            $this->logger->debug("MCP: Query params: " . json_encode($queryParams));

            // Check if this is an auth header test request
            if (isset($queryParams['test']) && $queryParams['test'] === 'auth') {
                return $this->handleAuthHeaderTest($request);
            }

            // Authenticate via Bearer token or query parameter
            $token = $this->extractToken($request);

            if (!$token) {
                $this->logger->error("MCP: No token found in Authorization header or query params");

                return $this->createUnauthorizedResponse('Missing authentication token');
            }

            $this->logger->debug("MCP: Received token: " . substr($token, 0, 20) . "...");

            $tokenInfo = $this->authService->validateToken($token);

            if ($tokenInfo === null) {
                $this->logger->error("MCP: Token validation failed for: " . substr($token, 0, 20) . "...");

                return $this->createUnauthorizedResponse('Invalid or expired token');
            }

            $this->logger->info("MCP: Token validation successful for user: " . $tokenInfo->userid);
            $this->setCurrentUser(new User($tokenInfo->userid));

            $server = new Server($this->config['server_name'] ?? 'Joomla MCP Server');

            // Register handlers
            $this->registerAbilities($server, $this->abilityRegistry);

            // Configure HTTP options
            $httpOptions = [
                'session_timeout' => $this->config['session_timeout'] ?? 1800, // 30 minutes
                'max_queue_size'  => $this->config['max_queue_size'] ?? 500,
                'enable_sse'      => $this->config['enable_sse'] ?? false,
                'shared_hosting'  => $this->config['shared_hosting'] ?? false,
            ];

            $sessionStore = new FileSessionStore(
                ($this->config['tmp_dir'] ?? JPATH_ROOT . '/tmp') . '/mcp_sessions'
            );

            // The SDK would normally write status, headers and body directly to PHP's
            // output (header(), echo). BufferedIo captures those writes in memory so
            // we can return a proper response object to the controller instead.
            $io = new BufferedIo();

            $runner = new HttpServerRunner(
                $server,
                $server->createInitializationOptions(),
                $httpOptions,
                null,
                $sessionStore,
                $io
            );

            // Suppress warnings/notices from MCP SDK to prevent deprecation issues
            $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);

            try {
                $response = $runner->handleRequest($request);
                $runner->sendResponse($response);
            } finally {
                // Restore error reporting
                error_reporting($oldErrorReporting);
            }

            // Forward the captured transport headers (Mcp-Session-Id, Content-Type, ...)
            $responseHeaders = [];

            foreach ($io->headers as [$name, $value]) {
                $responseHeaders[$name][] = $value;
            }

            // Pass the SDK output through byte-for-byte: a decode/re-encode round-trip
            // would turn empty JSON objects ({}) into empty arrays ([])
            $body = new Stream('php://temp', 'wb+');
            $body->write($io->buffer);
            $body->rewind();

            return new Response($body, $io->status ?? 200, $responseHeaders);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error'   => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Register MCP handlers
     *
     * @param Server          $server          Server instance
     * @param AbilityRegistry $abilityRegistry Tool registry
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function registerAbilities(Server $server, AbilityRegistry $abilityRegistry): void
    {
        // Register tool/list handler
        $server->registerHandler('tools/list', function () use ($abilityRegistry) {
            $tools = [];

            foreach ($abilityRegistry->getTools() as $tool) {
                $schema = $tool->getSchema();

                $toolDefinition = [
                    'name' => $tool->getName(),
                    ...$schema,  // Spread the entire schema (description, inputSchema, annotations)
                ];

                $tools[] = $toolDefinition;
            }

            return ['tools' => $tools];
        });

        // Register tool/call handler
        $server->registerHandler('tools/call', function ($params) use ($abilityRegistry) {
            $toolName  = $params->name;
            $arguments = $params->arguments;

            $tool = $abilityRegistry->getTool($toolName);

            if (!$tool) {
                throw new \InvalidArgumentException('Tool not found: ' . $toolName, 404);
            }

            return $tool->execute($arguments);
        });

        // Register resources/list handler
        $server->registerHandler('resources/list', function () use ($abilityRegistry) {
            $resources = [];

            foreach ($abilityRegistry->getResources() as $resource) {
                $resources[] = [
                    "uri"         => $resource->getUri(),
                    "name"        => $resource->getName(),
                    "title"       => $resource->getTitle(),
                    "description" => $resource->getDescription(),
                ];
            }

            return ['resources' => $resources];
        });


        // Register resources/read handler
        $server->registerHandler('resources/read', function ($params) use ($abilityRegistry) {
            $resource = $abilityRegistry->getResource($params->uri);

            if (!$resource) {
                throw new \InvalidArgumentException('Resource not found: ' . $params->uri, 404);
            }

            return $resource->read();
        });
    }

    /**
     * Extract token from request (Bearer header or query parameter)
     *
     * @param HttpMessage $request Request object
     *
     * @return  string|null  Token string or null if not found
     * @since   __DEPLOY_VERSION__
     */
    private function extractToken(HttpMessage $request): ?string
    {
        // Try Authorization header first (preferred method)
        $authHeader = $request->getHeader('Authorization') ?? '';

        // Try HTTP_AUTHORIZATION from the server environment
        if (empty($authHeader)) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }

        /**
         * Apache specific fix: mod_php does not expose the Authorization header in the
         * environment, only via apache_request_headers().
         * See https://github.com/symfony/symfony/issues/19693 and the same handling in
         * plg_api-authentication_token.
         */
        if (
            empty($authHeader) && \PHP_SAPI === 'apache2handler'
            && \function_exists('apache_request_headers') && apache_request_headers() !== false
        ) {
            $apacheHeaders = array_change_key_case(apache_request_headers(), CASE_LOWER);

            if (\array_key_exists('authorization', $apacheHeaders)) {
                $authHeader = $apacheHeaders['authorization'];
            }
        }

        // Another Apache specific fix (mod_rewrite/CGI setups pass the header only as
        // REDIRECT_HTTP_AUTHORIZATION). See https://github.com/symfony/symfony/issues/1813
        if (empty($authHeader)) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Fallback to query parameter for backward compatibility
        $queryParams = $request->getQueryParams();

        return $queryParams['token'] ?? null;
    }

    /**
     * Create unauthorized response
     *
     * @param string $message Error message
     *
     * @return  ResponseInterface  Response object
     * @since   __DEPLOY_VERSION__
     */
    private function createUnauthorizedResponse(string $message): ResponseInterface
    {
        return new JsonResponse([
            'error'   => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Handle auth header test request
     *
     * @param HttpMessage $request Request object
     *
     * @return ResponseInterface           Response object
     * @since  __DEPLOY_VERSION__
     */
    private function handleAuthHeaderTest(HttpMessage $request): ResponseInterface
    {
        $headers            = [];
        $receivedAuthHeader = false;

        // Check all possible ways the Authorization header might arrive
        $authHeader = $request->getHeader('Authorization');
        if (!empty($authHeader)) {
            $headers['authorization'] = $authHeader;
            $receivedAuthHeader       = true;
        }

        // Check server params for HTTP_AUTHORIZATION
        $serverParams = $_SERVER;
        if (isset($serverParams['HTTP_AUTHORIZATION'])) {
            $headers['http_authorization'] = $serverParams['HTTP_AUTHORIZATION'];
            $receivedAuthHeader            = true;
        }

        // Also check for redirect env variable (Apache specific)
        if (isset($serverParams['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['redirect_http_authorization'] = $serverParams['REDIRECT_HTTP_AUTHORIZATION'];
            $receivedAuthHeader                     = true;
        }

        return new JsonResponse(
            [
                'test'                 => 'auth',
                'headers_received'     => $headers,
                'auth_header_detected' => $receivedAuthHeader,
                'server_software'      => $serverParams['SERVER_SOFTWARE'] ?? 'unknown',
                'hint'                 => $receivedAuthHeader
                    ? 'Authorization header received successfully.'
                    : 'Authorization header not received.',
            ],
            200,
            [
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
            ]
        );
    }
}
