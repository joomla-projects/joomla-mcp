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

/** @var \Joomla\Component\MCP\Administrator\View\Mcp\HtmlView $this */

// Validierung und Verhaltensweisen laden
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<form action="<?php echo Route::_('index.php?option=com_mcp&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="adminForm" class="form-validate">

    <div class="main-card">
        <div class="row">
            <div class="col-lg-9">
                <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details']); ?>

                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('JDETAILS')); ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $this->form->renderFieldset('details'); ?>
                    </div>
                </div>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>

                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'custom', Text::_('COM_MCP_FIELD_ADDITIONAL_JSON_LABEL')); ?>
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $this->form->renderFieldset('custom'); ?>
                    </div>
                </div>
                <?php echo HTMLHelper::_('uitab.endTab'); ?>

                <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
            </div>

            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo Text::_('COM_MCP_GROUP_LABEL_PUBLISHING_DETAILS'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php echo $this->form->renderFieldset('publish'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
