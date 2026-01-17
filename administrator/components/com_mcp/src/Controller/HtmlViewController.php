<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\View\Mcps;

defined('_JEXEC') or die;

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

        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_MCP'), 'cog');

        ToolbarHelper::addNew('mcp.add');
        ToolbarHelper::editList('mcp.edit');
        ToolbarHelper::deleteList('', 'mcps.delete');
        ToolbarHelper::preferences('com_mcp');
    }
}
