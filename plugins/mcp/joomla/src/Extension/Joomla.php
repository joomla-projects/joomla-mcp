<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Mcp.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Mcp\Joomla\Extension;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\CMS\WebService\Internal\ComponentApiDispatcher;
use Joomla\CMS\WebService\Operation\ControllerClassResolver;
use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\CMS\WebService\Operation\RouterOperationDiscovery;
use Joomla\Component\MCP\Api\Event\RegisterMcpAbilitiesEvent;
use Joomla\Component\MCP\Api\Tool\InternalApiOperationInvoker;
use Joomla\Component\MCP\Api\Tool\WebserviceToolFactory;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Mcp\Joomla\Resource\ApplicationConfig;
use Joomla\Plugin\Mcp\Joomla\Resource\SysInfo;
use Joomla\Plugin\Mcp\Joomla\Tool\PurgeCache;

/**
 * Registers Joomla MCP abilities.
 *
 * @since  __DEPLOY_VERSION__
 */
final class Joomla extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RegisterMcpAbilitiesEvent::NAME => 'registerAbilities',
        ];
    }

    public function registerAbilities(RegisterMcpAbilitiesEvent $event): void
    {
        $this->loadLanguage();

        $event->addAbility(new PurgeCache());
        $event->addAbility(new ApplicationConfig());
        $event->addAbility(new SysInfo());

        $application = $this->getApplication();

        if (!$application instanceof CMSApplication || !$application->isClient('api')) {
            throw new \RuntimeException('Generated web service tools require the Joomla API application.');
        }

        $compiler = new OperationCompiler();
        $router = $application->getContainer()->get(ApiRouter::class);
        $discovery = new RouterOperationDiscovery(
            $router,
            $compiler,
            new ControllerClassResolver(),
        );
        $toolFactory = new WebserviceToolFactory(
            new InternalApiOperationInvoker(
                new OperationArgumentMapper(),
                new ComponentApiDispatcher($application),
            ),
        );

        foreach ($discovery->discover() as $operation) {
            if (!$operation->exposeToMcp) {
                continue;
            }

            $event->addAbility($toolFactory->create($operation));
        }
    }
}
