<?php

namespace Joomla\Plugin\Mcp\Joomla\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\MCP\Administrator\Event\InitialiseMCPServerEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Mcp\Joomla\Resource\ApplicationConfig;
use Joomla\Plugin\Mcp\Joomla\Resource\SysInfo;
use Joomla\Plugin\Mcp\Joomla\Tool\PurgeCache;

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
        // Load plugin language files for MCP API context
        $this->loadLanguage();

        // Register Tools
        $event->addAbility(new PurgeCache());

        // Register Resources
        $event->addAbility(new ApplicationConfig());
        $event->addAbility(new SysInfo());
    }
}
