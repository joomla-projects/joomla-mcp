<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\View\Mcp;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // Validierung: Falls das Model kein Formular liefert
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $isNew = ($this->item->id == 0);
        ToolbarHelper::title($isNew ? Text::_('COM_MCP_NEW') : Text::_('COM_MCP_EDIT'), 'cog');
        ToolbarHelper::apply('mcp.apply');
        ToolbarHelper::save('mcp.save');
        ToolbarHelper::cancel('mcp.cancel');
    }
}
