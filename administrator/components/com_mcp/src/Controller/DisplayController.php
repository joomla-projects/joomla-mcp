<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Controller for global configuration
 *e
 * @since  __DEPLOY_VERSION__
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $default_view = 'mcps';
}
