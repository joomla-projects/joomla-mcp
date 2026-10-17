<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Administrator\Field;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\MCP\InitialiseMCPServerEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\Language\Language;
use Joomla\CMS\MCP\Tool\ToolInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\User;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\CMS\WebAsset\WebAssetRegistry;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Input\Input;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Tests registered tool options and Joomla's standard list field handling.
 *
 * @since  __DEPLOY_VERSION__
 */
class McptoolsFieldTest extends UnitTestCase
{
    /**
     * Factory services and plugin cache restored after each test.
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    private array $originalServices;

    /**
     * Provide normal form dependencies without a Joomla database installation.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function setUp(): void
    {
        parent::setUp();

        $plugins                = new \ReflectionProperty(PluginHelper::class, 'plugins');
        $this->originalServices = [Factory::$application, Factory::$language, Factory::$container, $plugins->getValue()];
        $plugins->setValue(null, []);
        Factory::$language  = new Language('en-GB');
        Factory::$container = new Container();
        $dispatcher         = new Dispatcher();
        Factory::$container->set(DispatcherInterface::class, $dispatcher);
        Factory::$container->set(Registry::class, new Registry());

        foreach (['purgeCache', 'thirdParty.read'] as $name) {
            $tool = $this->createStub(ToolInterface::class);
            $tool->method('getName')->willReturn($name);
            $dispatcher->addListener('initialiseMCPServerEvent', static function (InitialiseMCPServerEvent $event) use ($tool): void {
                $event->addAbility($tool);
            });
        }

        $assets = $this->createStub(WebAssetManager::class);
        $assets->method('__call')->willReturnSelf();
        $assets->method('getRegistry')->willReturn($this->createStub(WebAssetRegistry::class));
        $document = $this->createStub(HtmlDocument::class);
        $document->method('getWebAssetManager')->willReturn($assets);
        $application = $this->createStub(CMSApplication::class);
        $application->method('getDocument')->willReturn($document);
        $application->method('getInput')->willReturn(new Input());
        Factory::$application = $application;
    }

    /**
     * Restore global services used by other Joomla tests.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function tearDown(): void
    {
        [Factory::$application, Factory::$language, Factory::$container, $plugins] = $this->originalServices;
        (new \ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, $plugins);

        parent::tearDown();
    }

    /**
     * Enabled third-party providers are imported for discovery without executing tools.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testOptionsIncludeImportedPluginToolsWithoutExecutingThem(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('anotherPlugin.inspect');
        $tool->expects($this->never())->method('getSchema');
        $tool->expects($this->never())->method('execute');
        $plugin = new class () extends CMSPlugin implements SubscriberInterface {
            public ToolInterface $tool;

            public static function getSubscribedEvents(): array
            {
                return ['initialiseMCPServerEvent' => 'initialise'];
            }

            public function initialise(InitialiseMCPServerEvent $event): void
            {
                $event->addAbility($this->tool);
            }
        };
        $plugin->tool = $tool;
        $application  = $this->createMock(CMSApplication::class);
        $application->expects($this->once())->method('bootPlugin')->with('tool_list_test', 'mcp')->willReturn($plugin);
        Factory::$application = $application;
        (new \ReflectionProperty(PluginHelper::class, 'plugins'))->setValue(null, [
            (object) ['type' => 'mcp', 'name' => 'tool_list_test'],
        ]);

        $field = $this->createForm()->getField('tools', 'params');

        $this->assertInstanceOf(ListField::class, $field);
        $this->assertSame(['anotherPlugin.inspect', 'purgeCache', 'thirdParty.read'], array_column($field->options, 'value'));
    }

    /**
     * The real XML form renders its multiple selection with Joomla's core layout.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testSelectionUsesTheStandardJoomlaListLayout(): void
    {
        $form = $this->createForm();
        $form->bind(['params' => ['tools' => ['thirdParty.read']]]);
        $html = $form->getField('tools', 'params')->input;

        $this->assertStringContainsString('<joomla-field-fancy-select', $html);
        $this->assertStringContainsString('name="jform[params][tools][]" multiple', $html);
        $this->assertStringContainsString('value="thirdParty.read" selected="selected"', $html);
        $this->assertStringNotContainsString('type="checkbox"', $html);
    }

    /**
     * Standard filtering retains tool names and validates them against registered options.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFormAcceptsRegisteredToolSelection(): void
    {
        $form     = $this->createForm();
        $filtered = $form->filter(['params' => [
            'tools_mode'           => 'exclude',
            'tools'                => ['purgeCache', 'thirdParty.read'],
            'exclude_descriptions' => '1',
        ]], 'params');

        $this->assertSame(['purgeCache', 'thirdParty.read'], $filtered['params']['tools']);
        $this->assertSame(1, $filtered['params']['exclude_descriptions']);
        $this->assertTrue($form->validate($filtered, 'params'));
        $filtered['params']['tools'] = ['notRegistered'];
        $this->assertFalse($form->validate($filtered, 'params'));
    }

    /**
     * Clearing a native multiple select submits no tool field and must remain valid.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFormAcceptsClearedSelectionAndRejectsUnknownModes(): void
    {
        $form     = $this->createForm();
        $filtered = $form->filter(['params' => ['tools_mode' => 'include', 'exclude_descriptions' => '0']], 'params');

        $this->assertArrayNotHasKey('tools', $filtered['params']);
        $this->assertTrue($form->validate($filtered, 'params'));
        $filtered['params']['tools_mode'] = 'all';
        $this->assertFalse($form->validate($filtered, 'params'));
        $filtered['params']['tools_mode'] = '';
        $this->assertFalse($form->validate($filtered, 'params'));
    }

    /**
     * Load the production form with its standard field filters and validators.
     *
     * @return  Form
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createForm(): Form
    {
        $form = new Form('com_mcp.mcp', ['control' => 'jform']);
        $form->setDatabase($this->createStub(DatabaseInterface::class));
        $form->setCurrentUser(new User());
        $form->loadFile(JPATH_ADMINISTRATOR . '/components/com_mcp/forms/mcp.xml');

        return $form;
    }
}
