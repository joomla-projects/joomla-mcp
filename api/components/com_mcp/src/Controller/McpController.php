<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\OAuth\ResourceServer\AccessTokenValidatorInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\MCP\Api\Auth\ResourceServerConfiguration;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Component\MCP\Api\Core\McpRequestContextFactory;
use Joomla\Component\MCP\Api\Core\ProtectedResourceMetadataProvider;
use Joomla\Component\MCP\Api\Core\ScopeAuthoriser;
use Joomla\Component\MCP\Api\Event\RegisterMcpAbilitiesEvent;
use Laminas\Diactoros\Response\JsonResponse;
use Mcp\Server\Transport\Http\HttpMessage;
use Psr\Http\Message\ResponseInterface;

/**
 * MCP API controller.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpController extends BaseController
{
    /**
     * Handles an incoming OAuth-protected MCP request.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function handle(): void
    {
        if (!ComponentHelper::getParams('com_mcp')->get('enabled', 0)) {
            $this->sendResponse(
                new JsonResponse(
                    ['error' => 'Service Unavailable', 'message' => 'The MCP server is disabled.'],
                    503,
                ),
            );

            return;
        }

        $configuration = $this->app->get('mcp.resourceServerConfiguration');
        $validator     = $this->app->get('mcp.accessTokenValidator');
        $contextFactory = $this->app->get('mcp.requestContextFactory');
        $metadataProvider = $this->app->get('mcp.protectedResourceMetadataProvider');
        $scopeAuthoriser  = $this->app->get('mcp.scopeAuthoriser');

        if (
            !$configuration instanceof ResourceServerConfiguration
            || !$validator instanceof AccessTokenValidatorInterface
            || !$contextFactory instanceof McpRequestContextFactory
            || !$metadataProvider instanceof ProtectedResourceMetadataProvider
            || !$scopeAuthoriser instanceof ScopeAuthoriser
        ) {
            $this->sendResponse(
                new JsonResponse(
                    [
                        'error'   => 'Service Unavailable',
                        'message' => 'The MCP OAuth Resource Server is not configured.',
                    ],
                    503,
                ),
            );

            return;
        }

        $endpoint = new McpEndpoint(
            $this->collectAbilities(),
            $validator,
            $contextFactory,
            $metadataProvider,
            $scopeAuthoriser,
            config: ['logger' => $this->logger],
        );
        $this->sendResponse($endpoint->handle(HttpMessage::fromGlobals()));
    }

    /**
     * Publishes OAuth Protected Resource Metadata for MCP clients.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function metadata(): void
    {
        $metadataProvider = $this->app->get('mcp.protectedResourceMetadataProvider');
        $scopeAuthoriser  = $this->app->get('mcp.scopeAuthoriser');

        if (
            !$metadataProvider instanceof ProtectedResourceMetadataProvider
            || !$scopeAuthoriser instanceof ScopeAuthoriser
        ) {
            $this->sendResponse(
                new JsonResponse(
                    [
                        'error'   => 'Service Unavailable',
                        'message' => 'The MCP OAuth Resource Server is not configured.',
                    ],
                    503,
                ),
            );

            return;
        }

        $abilities = $this->collectAbilities();
        $this->sendResponse(
            new JsonResponse(
                $metadataProvider->create($scopeAuthoriser->supportedScopes($abilities)),
                200,
                ['Cache-Control' => 'public, max-age=300'],
            ),
        );
    }

    /**
     * Responds to ping requests.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function ping(): void
    {
        $this->sendResponse(new JsonResponse(['pong' => true], 200));
    }

    /**
     * Sends a PSR-7 response to the client.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function sendResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            header($name . ': ' . implode(', ', $value));
        }

        echo $response->getBody();
        $this->app->close();
    }

    /**
     * Collects the available tools, resources and prompts.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function collectAbilities(): AbilityRegistry
    {
        $abilities = new AbilityRegistry();
        PluginHelper::importPlugin('mcp');
        $event = new RegisterMcpAbilitiesEvent($abilities);
        $this->getDispatcher()->dispatch($event->getName(), $event);

        return $abilities;
    }
}
