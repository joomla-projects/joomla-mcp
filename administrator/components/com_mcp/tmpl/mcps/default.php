<?php
/**
 * @package         Joomla.Administrator
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_mcp&view=mcps'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped" id="mcpList">
                        <thead>
                        <tr>
                            <th width="1%" class="text-center">
                                <?php echo HTMLHelper::_('grid.checkall'); ?>
                            </th>
                            <th width="1%" class="nowrap text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
                            </th>
                            <th class="nowrap">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_MCP_FIELD_CLIENT_NAME_LABEL', 'a.client_name', $listDirn, $listOrder); ?>
                            </th>
                            <th class="nowrap">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_MCP_FIELD_USERNAME_LABEL', 'a.username', $listDirn, $listOrder); ?>
                            </th>
                            <th width="10%" class="nowrap d-none d-md-table-cell">
                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_MCP_FIELD_CREATED_LABEL', 'a.created', $listDirn, $listOrder); ?>
                            </th>
                            <th width="1%" class="nowrap text-center">
                                <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->items as $i => $item) : ?>
                            <tr class="row<?php echo $i % 2; ?>">
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'mcps.', true); ?>
                                </td>
                                <td>
                                    <a href="<?php echo Route::_('index.php?option=com_mcp&task=mcp.edit&id=' . (int) $item->id); ?>">
                                        <?php echo $this->escape($item->client_name); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $this->escape($item->username); ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('date', $item->created, Text::_('DATE_FORMAT_LC4')); ?>
                                </td>
                                <td class="text-center">
                                    <?php echo (int) $item->id; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php echo $this->pagination->getListFooter(); ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
