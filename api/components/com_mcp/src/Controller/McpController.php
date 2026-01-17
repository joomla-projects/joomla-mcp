<?php declare(strict_types=1);
/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Application\WebApplicationInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\MCP\Administrator\Event\InitialiseMCPServerEvent;
use Joomla\Component\MCP\Api\Exception\NoWebApplicationException;
use Joomla\Input\Input;
use Mcp\Server\HttpServerRunner;
use Mcp\Server\NotificationOptions;
use Mcp\Server\Server;
use Mcp\Server\Transport\Http\HttpMessage;

/**
 * MCP API controller.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpController extends BaseController
{
	/**
	 * @var \Mcp\Server\Server
	 */
	private Server $server;

	/**
	 * @var \Mcp\Server\HttpServerRunner
	 */
	private HttpServerRunner $runner;

	/**
	 * Constructor.
	 *
	 * @param   array                     $config   An optional associative array of configuration settings.
	 *                                              Recognized key values include 'name', 'default_task',
	 *                                              'model_path', and 'view_path' (this list is not meant to be
	 *                                              comprehensive).
	 * @param   ?MVCFactoryInterface      $factory  The factory.
	 * @param   ?CMSApplicationInterface  $app      The Application for the dispatcher
	 * @param   ?Input                    $input    Input
	 *
	 * @since   3.0
	 */
	public function __construct(
		$config = [],
		?MVCFactoryInterface $factory = null,
		?CMSApplicationInterface $app = null,
		?Input $input = null
	)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->runner = $this->initialiseMCPServer();
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

		# $request  = new HttpMessage();
		# $request->setMethod($this->input->getMethod());
		# $request->setUri($route);
		# $request->setBody($this->input->getRaw('body', ''));
		# $request->setQueryParams($this->input->get('query', []));
		$request = HttpMessage::fromGlobals();

		$response = $this->runner->handleRequest($request);

		$this->runner->sendResponse($response);
		$this->app->close();
	}

	public function jsonRpc()
	{
		$requestData = HttpMessage::fromGlobals();
		$request     = json_decode($requestData->getBody(), true);

		$method = $request['method'];
		if (!method_exists($this, $method))
		{
			$responseData = [
				"code"  => 400,
				"error" => "Method $method not found",
			];
		}
		else
		{
			$params       = $request['params'];
			$responseData = $this->$method($params);
		}

		$this->sendResponse($responseData, $responseData['code']);
	}

	/**
	 * Send a response to the client.
	 *
	 * @param   array  $data
	 * @param          $code
	 *
	 * @return never
	 * @since __DEPLOY_VERSION__
	 */
	public function sendResponse(array $data, $code): never
	{
		$response = new HttpMessage(json_encode($data));
		$response->setStatusCode($code ?? 200);
		$response->setHeader('Content-Type', 'application/json');
		$this->runner->sendResponse($response);
		$this->app->close();
	}

	/**
	 * @param   array  $args        The arguments to initialize with. Example:
	 *                              {
	 *                                "protocolVersion": "2025-03-26",
	 *                                "capabilities": [],
	 *                                "clientInfo": {
	 *                                  "name": "MCP Test Client",
	 *                                  "version": "1.0.0"
	 *                                }
	 *                              }
	 *
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected function initialize($args): never
	{
		$this->sendResponse($args, 200);
	}

	/**
	 * Initialise the MCP server.
	 *
	 * @return \Mcp\Server\HttpServerRunner
	 * @since  __DEPLOY_VERSION__
	 */
	private function initialiseMCPServer(): HttpServerRunner
	{
		$this->server = new Server(
			'Joomla MCP Server',
			$this->logger
		);

		PluginHelper::importPlugin('mcp');
		$event = new InitialiseMCPServerEvent($this->server);
		$this->getDispatcher()->dispatch($event->getName(), $event);

		$initOptions = $this->server->createInitializationOptions(new NotificationOptions());
		$httpOptions = [];

		return new HttpServerRunner($this->server, $initOptions, $httpOptions, $this->logger);
	}
}
