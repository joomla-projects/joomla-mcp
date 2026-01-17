<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\MCP\Administrator\Event\InitialiseMCPServerEvent;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Component\MCP\Api\Core\ToolRegistry;
use Joomla\Input\Input;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Mcp\Server\HttpServerRunner;
use Mcp\Server\NotificationOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * MCP API controller.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpController extends BaseController
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
        $config = [],
        ?MVCFactoryInterface $factory = null,
        ?CMSApplicationInterface $app = null,
        ?Input $input = null
    ) {
        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Handle incoming HTTP request.
     *
     * @return void
     * @throws \Exception if the request cannot be handled.
     * @since  __DEPLOY_VERSION__
     */
    public function handle(): void
    {
        $route = $this->input->getPath('route', '');
        $this->logger->debug("Handling request '$route'");

        $request = ServerRequestFactory::fromGlobals();

        $toolRegistry = $this->collectHandlers();
        $authService  = $this->authService;
        $config       = ['logger' => $this->logger];
        $endpoint     = new McpEndpoint($toolRegistry, $authService, $config);

        $response = $endpoint($request);

        $this->sendResponse($response);
        $this->app->close();
    }

    /**
     * Respond to ping requests.
     *
     * @return void
     * @since  __DEPLOY_VERSION__
     */
    public function ping(): void
    {
        $this->sendResponse(new JsonResponse(['pong' => true], 200));
    }

    /**
     * Send a response to the client.
     *
     * @param ResponseInterface $response
     *
     * @return void
     * @since __DEPLOY_VERSION__
     */
    private function sendResponse(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode() ?? 200);

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
     * Collect the available tools, resources and prompts
     *
     * @return ToolRegistry
     * @since  __DEPLOY_VERSION__
     */
    private function collectHandlers(): ToolRegistry
    {
        $tools = new ToolRegistry([]);

        PluginHelper::importPlugin('mcp');
        $event = new InitialiseMCPServerEvent($tools);
        $this->getDispatcher()->dispatch($event->getName(), $event);

        return $tools;
    }
}
