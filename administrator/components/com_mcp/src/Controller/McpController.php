<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Application\ApplicationHelper;

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

    /**
     * Method to regenerate the client token.
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function regenerateToken()
    {
        // Check for request forgeries.
        $this->checkToken();

        $app = $this->app;
        $model = $this->getModel();
        $id = $app->getInput()->getInt('id');

        if (!$id) {
            $app->enqueueMessage(Text::_('COM_MCP_ERROR_INVALID_ID'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mcp&view=mcps', false));
            return;
        }

        // Load the item
        $item = $model->getItem($id);

        if (!$item || !$item->id) {
            $app->enqueueMessage(Text::_('COM_MCP_ERROR_ITEM_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_mcp&view=mcps', false));
            return;
        }

        // Generate new token (same logic as in McpTable)
        $newToken = ApplicationHelper::getHash(microtime() . $item->id);

        // Update the token in the database
        $data = [
            'id' => $id,
            'client_token' => $newToken,
        ];

        if ($model->save($data)) {
            $app->enqueueMessage(Text::_('COM_MCP_TOKEN_REGENERATED_SUCCESS'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_MCP_TOKEN_REGENERATED_ERROR'), 'error');
        }

        // Redirect back to the edit view
        $this->setRedirect(Route::_('index.php?option=com_mcp&view=mcp&layout=edit&id=' . $id, false));
    }
}
