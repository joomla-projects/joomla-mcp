<?php
declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

/**
 * MCP Controller for the list of records.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpsController extends AdminController
{
    /**
     * Method to get a model object, load it if necessary.
     *
     * @param string $name The model name. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array $config Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel  The model.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getModel($name = 'Mcp', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
