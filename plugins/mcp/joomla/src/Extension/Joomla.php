<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Mcp.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Mcp\Joomla\Extension;

use Joomla\CMS\Mcp\Tool\InternalApiOperationInvoker;
use Joomla\CMS\Mcp\Tool\WebserviceToolProvider;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Component\MCP\Administrator\Event\InitialiseMCPServerEvent;
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
            new InternalApiOperationInvoker(),
        );

        foreach ($provider->getTools(ArticlesController::class) as $tool) {
            $event->addAbility($tool);
        }
    }
}
