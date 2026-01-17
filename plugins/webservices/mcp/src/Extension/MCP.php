<?php declare(strict_types=1);
/**
 * @package         Joomla.MCP
 * @subpackage      plg_webservices_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

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
     * Returns an array of events that this subscriber wants to listen to.
     *
     * @return string[]  The event names to subscribe to.
     *                   Format: [eventName => methodName].
     * @since  __DEPLOY_VERSION__
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Handle the onBeforeApiRoute event.
     *
     * Registers the routes (`/api/v1/mcp/*route`) for the MCP Web Services extension.
     * The MCP route is provided in `$application->input->getCmd('route')`.
     *
     * @param  \Joomla\CMS\Event\Application\BeforeApiRouteEvent  $event  The event object.
     *
     * @return void
     * @since  __DEPLOY_VERSION__
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();

        $defaults = [
            'component' => 'com_mcp',
            'public'    => true,
        ];

        $router->addRoute(new Route(['GET'], 'v1/mcp/ping', 'mcp.ping', [], $defaults));

        # This catch-all route MUST be the last one!
        $router->addRoute(new Route(
            ['GET', 'POST'],
            'v1/mcp/*route',
            'mcp.handle',
            [],
            $defaults
        ));
    }
}
