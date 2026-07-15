<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Component\MCP\Api\Event\RegisterMcpAbilitiesEvent;
use Laminas\Diactoros\Response\JsonResponse;
use Mcp\Server\Transport\Http\HttpMessage;
use Psr\Http\Message\ResponseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * MCP API controller.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpController extends BaseController
{
    /**
     * Handles an incoming MCP HTTP request.
     *
     * @return  void
     *
     * @throws  \Exception  If the request cannot be handled.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function handle(): void
    {
        $route = $this->input->getPath('route', '');
        $this->logger->debug("Handling request '$route'");

        if (!ComponentHelper::getParams('com_mcp')->get('enabled', 0)) {
            $this->logger->warning("Rejected request '$route': the MCP server is disabled.");
            $this->sendResponse(
                new JsonResponse(
                    [
                        'error'   => 'Service Unavailable',
                        'message' => 'The MCP server is disabled.',
                    ],
                    503,
                ),
            );

            return;
        }

        $abilityRegistry = $this->collectAbilities();
        $authService     = $this->app->get('mcp.authService');
        $config          = ['logger' => $this->logger];
        $endpoint        = new McpEndpoint($abilityRegistry, $authService, $config);
        $request         = HttpMessage::fromGlobals();
        $result          = $endpoint->handle($request);

        $this->sendResponse($result);
        $this->app->close();
    }

    /**
     * Responds to ping requests.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function ping(): void
    {
        $this->sendResponse(new JsonResponse(['pong' => true], 200));
    }

    /**
     * Sends a response to the client.
     *
     * @param ResponseInterface $response Response object.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private function sendResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            if (\is_array($value)) {
                $value = implode(', ', $value);
            }

            header("$name: $value");
        }

        echo $response->getBody();
        $this->app->close();
    }

    /**
     * Collects the available tools, resources and prompts.
     *
     * @return  AbilityRegistry
     *
     * @since   __DEPLOY_VERSION__
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
