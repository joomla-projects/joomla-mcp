<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_config
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\View\Options;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * View for the global configuration
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends BaseHtmlView
{
    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->addToolbar();
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function addToolbar()
    {
        $user     = $this->getCurrentUser();
        $toolbar  = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MCP'), 'cog config');

        if ($user->authorise('core.admin', 'com_mcp') || $user->authorise('core.options', 'com_mcp')) {
            $toolbar->preferences('com_mcp');
        }

        $toolbar->help('Articles');
    }
}
