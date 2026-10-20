<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Administrator;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\Component\MCP\Administrator\Controller\ClientController;
use Joomla\Component\MCP\Administrator\Controller\ClientsController;
use Joomla\Component\MCP\Administrator\Helper\TokenHelper;
use Joomla\Component\MCP\Administrator\Model\ClientModel;
use Joomla\Component\MCP\Administrator\Model\ClientsModel;
use Joomla\Component\MCP\Administrator\Table\McpTable;
use Joomla\Component\MCP\Administrator\View\Client\HtmlView as ClientView;
use Joomla\Component\MCP\Administrator\View\Clients\HtmlView as ClientsView;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\NullLogger;

/**
 * Exercise the administrator MVC names through Joomla's actual factory and forms.
 *
 * Separate processes keep the component path and Joomla's static caches local to each test.
 *
 * @since  __DEPLOY_VERSION__
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ClientMvcTest extends UnitTestCase
{
    private MVCFactory $factory;
    private AdministratorApplication $app;
    private Dispatcher $dispatcher;
    private array $session = [];

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function setUp(): void
    {
        parent::setUp();

        \define('JPATH_COMPONENT', JPATH_ADMINISTRATOR . '/components/com_mcp');
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/administrator/index.php';

        $db = $this->createStub(DatabaseDriver::class);
        $db->method('getTableColumns')->willReturn(['id' => (object) ['Type' => 'int', 'Default' => null]]);
        $db->method('quoteName')->willReturnArgument(0);
        $db->method('getQuery')->willReturn($this->getQueryStub($db));
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadObjectList')->willReturn([]);

        $this->dispatcher = new Dispatcher();
        $this->app        = $this->createStub(AdministratorApplication::class);
        $this->app->method('getName')->willReturn('administrator');
        $this->app->method('getInput')->willReturn(new Input());
        $this->app->method('getIdentity')->willReturn(new User());
        $this->app->method('getDispatcher')->willReturn($this->dispatcher);
        $this->app->method('getLogger')->willReturn(new NullLogger());
        $this->app->method('getTemplate')->willReturn((object) ['template' => 'atum', 'parent' => '']);
        $this->app->method('getUserState')->willReturnCallback(fn ($key, $default = null) => $this->session[$key] ?? $default);

        Factory::$application = $this->app;
        Factory::$database    = $db;
        Factory::$container   = (new Container())->set('config', new Registry(['live_site' => 'http://localhost']));

        // No installed plugins are needed to exercise form loading and preparation events.
        (new \ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, []);

        $forms = new FormFactory();
        $forms->setDatabase($db);

        $this->factory = new MVCFactory('Joomla\\Component\\MCP');
        $this->factory->setDatabase($db);
        $this->factory->setDispatcher($this->dispatcher);
        $this->factory->setFormFactory($forms);
        $this->factory->setCacheControllerFactory($this->createStub(CacheControllerFactoryInterface::class));
        $this->factory->setLanguageFactory($this->createStub(LanguageFactoryInterface::class));
    }

    /**
     * Both task prefixes must resolve to controllers that operate on the client model.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientTaskControllersResolveTheRecordModel(): void
    {
        $client  = $this->factory->createController('client', 'Administrator', [], $this->app, new Input());
        $clients = $this->factory->createController('clients', 'Administrator', [], $this->app, new Input());

        $this->assertInstanceOf(ClientController::class, $client);
        $this->assertInstanceOf(ClientsController::class, $clients);
        $this->assertInstanceOf(ClientModel::class, $client->getModel());
        $this->assertInstanceOf(ClientModel::class, $clients->getModel());
    }

    /**
     * The public view names must resolve to matching views and models.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientViewsResolveWithTheirModels(): void
    {
        $config  = ['option' => 'com_mcp', 'base_path' => JPATH_COMPONENT];
        $client  = $this->factory->createView('client', 'Administrator', 'Html', $config);
        $clients = $this->factory->createView('clients', 'Administrator', 'Html', $config);

        $this->assertInstanceOf(ClientView::class, $client);
        $this->assertInstanceOf(ClientsView::class, $clients);
        $this->assertSame('client', $client->getName());
        $this->assertSame('clients', $clients->getName());
        $this->assertInstanceOf(ClientModel::class, $this->factory->createModel($client->getName(), 'Administrator', ['ignore_request' => true]));
        $this->assertInstanceOf(ClientsModel::class, $this->factory->createModel($clients->getName(), 'Administrator', ['ignore_request' => true]));
    }

    /**
     * The renamed model must keep existing client records and history identities.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientModelResolvesTheExistingDatabaseTable(): void
    {
        $model = $this->factory->createModel('Client', 'Administrator', ['ignore_request' => true]);
        $table = $model->getTable();

        $this->assertInstanceOf(McpTable::class, $table);
        $this->assertSame('#__mcp', $table->getTableName());
        $this->assertSame('com_mcp.mcp', $table->getTypeAlias());
    }

    /**
     * A validation failure must restore the data saved under the client controller's context.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientFormRestoresClientEditSessionData(): void
    {
        $this->session['com_mcp.edit.client.data'] = ['id' => 7, 'client_name' => 'Unsaved client'];
        $this->session['com_mcp.edit.mcp.data']    = ['id' => 3, 'client_name' => 'Old session'];
        $contexts                                  = [];
        $this->dispatcher->addListener('onContentPrepareData', function ($event) use (&$contexts): void {
            $contexts[] = $event->getArgument('context');
        });

        $model = $this->factory->createModel('Client', 'Administrator', ['ignore_request' => true]);
        $form  = $model->getForm();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame('com_mcp.client', $form->getName());
        $this->assertSame('Unsaved client', $form->getValue('client_name'));
        $this->assertSame(7, $form->getValue('id'));
        $this->assertSame(['com_mcp.client'], $contexts);
    }

    /**
     * ClientsModel must discover filter_clients.xml and restore its own filter state.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientsFilterFormLoadsAndRestoresListState(): void
    {
        $this->session['com_mcp.clients'] = (object) ['filter' => ['search' => 'Desktop client'], 'list' => []];
        $model                            = $this->factory->createModel('Clients', 'Administrator', ['ignore_request' => true]);
        $form                             = $model->getFilterForm();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame('com_mcp.clients.filter', $form->getName());
        $this->assertSame('Desktop client', $form->getValue('search', 'filter'));
        $this->assertSame('mcpuser', (string) $form->getFieldXml('username', 'filter')['type']);
    }
    /**
     * A record loaded for editing must expose the derived token, never its seed.
     */
    public function testEditItemReplacesSeedWithDerivedToken(): void
    {
        $this->app->method('get')->willReturnCallback(fn ($key, $default = null) => $key === 'secret' ? 'site-secret' : $default);
        $seed                = TokenHelper::generateSeed();
        $table               = $this->createMock(McpTable::class);
        $table->id           = 42;
        $table->client_token = $seed;
        $table->expects($this->once())->method('load')->with(42)->willReturn(true);
        $model = $this->getMockBuilder(ClientModel::class)->setConstructorArgs([['ignore_request' => true]])->onlyMethods(['getTable'])->getMock();
        $model->expects($this->once())->method('getTable')->willReturn($table);

        $item = $model->getItem(42);

        $this->assertSame(TokenHelper::buildToken(42, $seed), $item->client_token);
        $this->assertNotSame($seed, $item->client_token);
        $this->assertSame($seed, $table->client_token);
    }

    /**
     * Form saves must strip browser-supplied tokens before they can replace a seed.
     */
    public function testValidationDropsSubmittedTokenAndKeepsClientData(): void
    {
        $form = $this->createMock(Form::class);
        $data = ['id' => 42, 'client_name' => 'Edited client', 'client_token' => 'browser-controlled-token'];
        $form->expects($this->once())->method('process')->with($data, null)->willReturn($data);
        $model = $this->factory->createModel('Client', 'Administrator', ['ignore_request' => true]);

        $this->assertSame(['id' => 42, 'client_name' => 'Edited client'], $model->validate($form, $data));
    }

    /**
     * Verify the public navigation and form task contract alongside factory resolution.
     */
    public function testAdministratorUrlsAndActionsUseClientNames(): void
    {
        $manifest = simplexml_load_file(JPATH_COMPONENT . '/mcp.xml');
        $this->assertSame('option=com_mcp&view=clients', (string) $manifest->administration->menu['link']);
        $controller = $this->factory->createController('client', 'Administrator', [], $this->app, new Input());
        $this->assertSame('clients', (new \ReflectionProperty($controller, 'view_list'))->getValue($controller));
        foreach (['add', 'edit', 'apply', 'save', 'cancel', 'regeneratetoken'] as $task) {
            $this->assertArrayHasKey($task, (new \ReflectionProperty($controller, 'taskMap'))->getValue($controller));
        }
        $controller = $this->factory->createController('clients', 'Administrator', [], $this->app, new Input());
        foreach (['publish', 'unpublish', 'archive', 'trash', 'delete', 'checkin', 'saveorderajax'] as $task) {
            $this->assertArrayHasKey($task, (new \ReflectionProperty($controller, 'taskMap'))->getValue($controller));
        }
        $display = $this->factory->createController('display', 'Administrator', [], $this->app, new Input());
        $this->assertSame('clients', (new \ReflectionProperty($display, 'default_view'))->getValue($display));
        $edit  = file_get_contents(JPATH_COMPONENT . '/tmpl/client/edit.php');
        $list  = file_get_contents(JPATH_COMPONENT . '/tmpl/clients/default.php');
        $empty = file_get_contents(JPATH_COMPONENT . '/tmpl/clients/emptystate.php');
        $this->assertStringContainsString('option=com_mcp&view=client&layout=edit&id=', $edit);
        $this->assertStringContainsString('option=com_mcp&view=clients', $list);
        $this->assertStringContainsString('task=client.edit&id=', $list);
        $this->assertStringContainsString('task=clients.saveOrderAjax', $list);
        $this->assertStringContainsString("'clients.'", $list);
        $this->assertStringContainsString('view=clients', $empty);
        $this->assertStringContainsString('task=client.add', $empty);
    }

    /**
     * Regeneration must keep upstream seed generation and return to the client URL.
     */
    public function testRegenerationSavesNewSeedAndRedirectsToClient(): void
    {
        $router = $this->createStub(\Joomla\CMS\Router\Router::class);
        $router->method('build')->willReturnCallback(fn ($url) => new \Joomla\CMS\Uri\Uri($url));
        Factory::getContainer()->set('AdministratorRouter', $router);
        Factory::$language = new \Joomla\CMS\Language\Language('en-GB');
        $this->app->getInput()->set('id', 42);
        $model = $this->createMock(ClientModel::class);
        $model->expects($this->once())->method('getItem')->with(42)->willReturn((object) ['id' => 42]);
        $model->expects($this->once())->method('save')->willReturnCallback(function ($data) {
            $this->assertSame(['id', 'client_token'], array_keys($data));
            $this->assertSame(42, $data['id']);
            $this->assertSame(32, \strlen(base64_decode($data['client_token'], true)));
            $this->assertNull(TokenHelper::parseToken($data['client_token']));
            return true;
        });
        $controller = $this->getMockBuilder(ClientController::class)
            ->setConstructorArgs([[], $this->factory, $this->app, $this->app->getInput()])
            ->onlyMethods(['getModel', 'checkToken', 'setRedirect'])->getMock();
        $controller->expects($this->once())->method('checkToken')->willReturn(true);
        $controller->method('getModel')->willReturn($model);
        $controller->expects($this->once())->method('setRedirect')
            ->with('index.php?option=com_mcp&view=client&layout=edit&id=42')->willReturnSelf();

        $controller->regenerateToken();
    }

}
