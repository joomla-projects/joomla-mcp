<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\View\Mcps;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\ListView;

/**
 * View class for a list of MCPs.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends ListView
{
    /**
     * The help link for the view
     *
     * @var string
     */
    protected $helpLink = 'MCP_Servers';

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(array $config)
    {
        if (empty($config['option'])) {
            $config['option'] = 'com_mcp';
        }

        $config['toolbar_title']  = 'COM_MCP_MANAGER_MCPS';
        $config['toolbar_icon']   = 'cog mcp';
        $config['supports_batch'] = false;

        parent::__construct($config);
    }

    /**
     * Prepare view data
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function initializeView()
    {
        parent::initializeView();

        $this->canDo = ContentHelper::getActions('com_mcp');
    }
}
