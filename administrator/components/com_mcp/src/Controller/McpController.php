<?php

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Component\MCP\Administrator\Helper\TokenHelper;

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

        $app   = $this->app;
        $model = $this->getModel();
        $id    = $app->getInput()->getInt('id');

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

        // Generate a new token seed; the client token is derived from it
        $data = [
            'id'           => $id,
            'client_token' => TokenHelper::generateSeed(),
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
