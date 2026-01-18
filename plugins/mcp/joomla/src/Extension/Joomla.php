<?php

namespace Joomla\Plugin\Mcp\Joomla\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\MCP\Administrator\Event\InitialiseMCPServerEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Mcp\Joomla\Tool\PurgeCache;

final class Joomla extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'initialiseMCPServerEvent' => 'registerTools'
        ];
    }

    public function registerTools(InitialiseMCPServerEvent $event): void
    {
        $event->addTool(new PurgeCache());
    }
}
