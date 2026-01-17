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
        //$this->form = $this->get('Form');
        //$this->item = $this->get('Item');

        $model       = $this->getModel();
        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();
        // Validierung: Falls das Model kein Formular liefert
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        // Hole den aktuell eingeloggten Benutzer
        $user = \Joomla\CMS\Factory::getApplication()->getIdentity();

        $isNew = ($this->item->id == 0);
        ToolbarHelper::title($isNew ? Text::_('COM_MCP_NEW') : Text::_('COM_MCP_EDIT'), 'cog');
        ToolbarHelper::apply('mcp.apply');
        ToolbarHelper::save('mcp.save');
        ToolbarHelper::cancel('mcp.cancel');

        // Prüfen, ob der Nutzer die Berechtigung hat, die Optionen zu sehen
        if ($user->authorise('core.admin', 'com_mcp') || $user->authorise('core.options', 'com_mcp')) {
            ToolbarHelper::preferences('com_mcp');
        }
    }
}
