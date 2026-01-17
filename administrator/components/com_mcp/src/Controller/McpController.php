<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

/**
 * MCP Controller for a single record.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpController extends FormController
{
    /**
     * The default view for the list.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $view_list = 'mcps';
}
