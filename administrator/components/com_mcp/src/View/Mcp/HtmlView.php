<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\View\Mcp;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $user = \Joomla\CMS\Factory::getApplication()->getIdentity();

        $isNew = ($this->item->id == 0);
        ToolbarHelper::title($isNew ? Text::_('COM_MCP_FORM_TITLE_NEW') : Text::_('COM_MCP_FORM_TITLE_EDIT'), 'cog');

        // Add regenerate token button for existing items
        if (!$isNew) {
            $toolbar = \Joomla\CMS\Toolbar\Toolbar::getInstance();
            $toolbar->standardButton('refresh', 'COM_MCP_TOOLBAR_REGENERATE_TOKEN', 'mcp.regenerateToken')
                ->icon('icon-refresh')
                ->listCheck(false);
        }

        ToolbarHelper::apply('mcp.apply');
        ToolbarHelper::save('mcp.save');
        ToolbarHelper::cancel('mcp.cancel');

        if ($user->authorise('core.admin', 'com_mcp') || $user->authorise('core.options', 'com_mcp')) {
            ToolbarHelper::preferences('com_mcp');
        }
    }
}
