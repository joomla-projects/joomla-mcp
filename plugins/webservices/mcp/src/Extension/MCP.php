<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  WebServices.MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Plugin\WebServices\MCP\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

/**
 * MCP Web Services Plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
final class MCP extends CMSPlugin implements SubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return ['onBeforeApiRoute' => 'onBeforeApiRoute'];
    }

    /**
     * Registers the protected MCP endpoint and its public resource metadata endpoint.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router   = $event->getRouter();
        $defaults = [
            'component' => 'com_mcp',
            'public'    => true,
            'format'    => ['application/json'],
        ];

        $router->addRoute(
            new Route(
                ['GET'],
                'v1/mcp/oauth-protected-resource',
                'mcp.metadata',
                [],
                $defaults,
            ),
        );

        // This catch-all route must remain the final MCP route.
        $router->addRoute(
            new Route(
                ['GET', 'POST'],
                'v1/mcp',
                'mcp.handle',
                [],
                $defaults,
            ),
        );
    }
}
