<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\OAuth\ResourceServer\AccessTokenValidatorInterface;
use Joomla\CMS\OAuth\ResourceServer\TokenValidationException;
use Joomla\Component\MCP\Api\Auth\SubjectResolutionException;
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
 * OAuth-protected MCP HTTP endpoint.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpEndpoint
{
    private LoggerInterface $logger;

    /**
     * @param  array<string, mixed>  $config  MCP HTTP runner configuration.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly AbilityRegistry $abilityRegistry,
        private readonly AccessTokenValidatorInterface $accessTokenValidator,
        private readonly McpRequestContextFactory $contextFactory,
        private readonly ProtectedResourceMetadataProvider $metadataProvider,
        private readonly ScopeAuthoriser $scopeAuthoriser,
        private readonly BearerTokenExtractor $tokenExtractor = new BearerTokenExtractor(),
        private readonly array $config = [],
    ) {
        $this->logger = $this->config['logger'] ?? new NullLogger();
    }

    /**
     * Handles an OAuth-protected MCP HTTP request.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function handle(HttpMessage $request): ResponseInterface
    {
        $requestId = $this->requestId($request);

        try {
            $token = $this->tokenExtractor->extract($request);

            if ($token === null) {
                return $this->authenticationResponse('Missing OAuth access token.');
            }

            $principal = $this->accessTokenValidator->validate(
                $token,
                $this->metadataProvider->getResource(),
            );
            $this->scopeAuthoriser->assertBaseAccess($principal);
            $context = $this->contextFactory->create(
                $principal,
                $this->metadataProvider->getResource(),
                $requestId,
            );

            return $this->runServer($request, $context);
        } catch (TokenValidationException | SubjectResolutionException $exception) {
            $this->logger->notice('MCP access token rejected.', ['request_id' => $requestId]);

            return $this->authenticationResponse('Invalid or expired OAuth access token.', true);
        } catch (InsufficientScopeException $exception) {
            $this->logger->notice(
                'MCP access token lacks a required scope.',
                ['request_id' => $requestId, 'scopes' => $exception->requiredScopes],
            );

            return $this->scopeResponse($exception->requiredScopes);
        } catch (McpAccessDeniedException $exception) {
            $this->logger->notice('Joomla MCP access denied.', ['request_id' => $requestId]);

            return new JsonResponse(
                ['error' => 'Forbidden', 'message' => 'Access to the MCP server is not permitted.'],
                403,
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'The MCP endpoint failed.',
                ['request_id' => $requestId, 'exception' => $exception],
            );

            return new JsonResponse(
                ['error' => 'Internal Server Error', 'message' => 'The MCP request could not be processed.'],
                500,
            );
        }
    }

    /**
     * Runs the MCP SDK under an authenticated request context.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function runServer(HttpMessage $request, McpRequestContext $context): ResponseInterface
    {
        $server = new Server($this->config['server_name'] ?? 'Joomla MCP Server');
        $this->registerAbilities($server, $this->abilityRegistry, $context);

        $httpOptions = [
            'session_timeout' => $this->config['session_timeout'] ?? 1800,
            'max_queue_size'  => $this->config['max_queue_size'] ?? 500,
            'enable_sse'      => $this->config['enable_sse'] ?? false,
            'shared_hosting'  => $this->config['shared_hosting'] ?? false,
        ];
        $sessionStore = new FileSessionStore(
            ($this->config['tmp_dir'] ?? JPATH_ROOT . '/tmp') . '/mcp_sessions',
        );
        $io     = new BufferedIo();
        $runner = new HttpServerRunner(
            $server,
            $server->createInitializationOptions(),
            $httpOptions,
            null,
            $sessionStore,
            $io,
        );

        $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);

        try {
            $response = $runner->handleRequest($request);
            $runner->sendResponse($response);
        } finally {
            error_reporting($oldErrorReporting);
        }

        $responseHeaders = [];

        foreach ($io->headers as [$name, $value]) {
            $responseHeaders[$name][] = $value;
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($io->buffer);
        $body->rewind();

        return new Response($body, $io->status ?? 200, $responseHeaders);
    }

    /**
     * Registers context-aware MCP handlers.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function registerAbilities(
        Server $server,
        AbilityRegistry $abilityRegistry,
        McpRequestContext $context,
    ): void {
        $server->registerHandler('tools/list', function () use ($abilityRegistry, $context): array {
            $tools = [];

            foreach ($abilityRegistry->getTools() as $tool) {
                if (!$this->scopeAuthoriser->canUseTool($context->principal, $tool)) {
                    continue;
                }

                $tools[] = ['name' => $tool->getName(), ...$tool->getSchema()];
            }

            return ['tools' => $tools];
        });

        $server->registerHandler('tools/call', function ($params) use ($abilityRegistry, $context) {
            $tool = $abilityRegistry->getTool($params->name);

            if ($tool === null) {
                throw new \InvalidArgumentException('Tool not found.', 404);
            }

            $this->scopeAuthoriser->assertToolAccess($context->principal, $tool);
            $arguments = (array) $params->arguments;

            return $tool->execute($arguments, $context);
        });

        $server->registerHandler('resources/list', function () use ($abilityRegistry): array {
            $resources = [];

            foreach ($abilityRegistry->getResources() as $resource) {
                $resources[] = [
                    'uri'         => $resource->getUri(),
                    'name'        => $resource->getName(),
                    'title'       => $resource->getTitle(),
                    'description' => $resource->getDescription(),
                ];
            }

            return ['resources' => $resources];
        });

        $server->registerHandler('resources/read', function ($params) use ($abilityRegistry) {
            $resource = $abilityRegistry->getResource($params->uri);

            if ($resource === null) {
                throw new \InvalidArgumentException('Resource not found.', 404);
            }

            return $resource->read();
        });
    }

    /**
     * Creates an OAuth Bearer authentication response.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function authenticationResponse(string $message, bool $invalid = false): ResponseInterface
    {
        $parameters = [
            'resource_metadata' => $this->metadataProvider->getMetadataUri(),
            'scope'             => 'mcp:use',
        ];

        if ($invalid) {
            $parameters = ['error' => 'invalid_token', ...$parameters];
        }

        return new JsonResponse(
            ['error' => 'Unauthorized', 'message' => $message],
            401,
            ['WWW-Authenticate' => $this->bearerChallenge($parameters)],
        );
    }

    /**
     * Creates an insufficient-scope response.
     *
     * @param  list<string>  $scopes  Required scopes.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function scopeResponse(array $scopes): ResponseInterface
    {
        return new JsonResponse(
            ['error' => 'Forbidden', 'message' => 'The access token lacks a required OAuth scope.'],
            403,
            [
                'WWW-Authenticate' => $this->bearerChallenge(
                    [
                        'error'             => 'insufficient_scope',
                        'scope'             => implode(' ', $scopes),
                        'resource_metadata' => $this->metadataProvider->getMetadataUri(),
                    ],
                ),
            ],
        );
    }

    /**
     * Builds a safe OAuth Bearer challenge.
     *
     * @param  array<string, string>  $parameters  Challenge parameters.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function bearerChallenge(array $parameters): string
    {
        $values = [];

        foreach ($parameters as $name => $value) {
            $values[] = $name . '="' . addcslashes($value, "\\\"") . '"';
        }

        return 'Bearer ' . implode(', ', $values);
    }

    /**
     * Returns or generates a request identifier without exposing credentials.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function requestId(HttpMessage $request): string
    {
        $requestId = trim((string) ($request->getHeader('Mcp-Request-Id') ?? ''));

        if ($requestId !== '' && preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $requestId) === 1) {
            return $requestId;
        }

        return bin2hex(random_bytes(16));
    }
}
