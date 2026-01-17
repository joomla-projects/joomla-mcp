<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\View\Mcps;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;

    public function display($tpl = null)
    {
        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state      = $this->get('State');

        if ($this->getLayout() === 'default' || !$this->getLayout()) {
            $this->setLayout('default');
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $user = Factory::getApplication()->getIdentity();

        ToolbarHelper::title(Text::_('COM_MCP'), 'cog');
        ToolbarHelper::addNew('mcp.add');
        ToolbarHelper::editList('mcp.edit');
        ToolbarHelper::deleteList('', 'mcps.delete');
        if ($user->authorise('core.admin', 'com_mcp') || $user->authorise('core.options', 'com_mcp')) {
            ToolbarHelper::preferences('com_mcp');
        }
    }
}
