<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Mcp.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MCP\Joomla\Extension;

use Joomla\CMS\Event\MCP\InitialiseMCPServerEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\MCP\Joomla\Resource\ApplicationConfig;
use Joomla\Plugin\MCP\Joomla\Resource\SysInfo;
use Joomla\Plugin\MCP\Joomla\Tool\PurgeCache\PurgeCache;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Dispatcher\WebserviceDispatcher;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\WebserviceToolProvider;

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
            'initialiseMCPServerEvent' => 'registerAbilities',
        ];
    }

    public function registerAbilities(InitialiseMCPServerEvent $event): void
    {
        $this->loadLanguage();

        $event->addAbility(new PurgeCache());
        $event->addAbility(new ApplicationConfig());
        $event->addAbility(new SysInfo());

        $provider = new WebserviceToolProvider(
            new OperationCompiler(),
            new WebserviceDispatcher(),
        );

        foreach ($provider->getTools(ArticlesController::class) as $tool) {
            $event->addAbility($tool);
        }
    }
}
