<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;

/**
 * Form Field to load a list of users that have MCP clients
 *
 * @since  __DEPLOY_VERSION__
 */
class McpuserField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Mcpuser';

    /**
     * Method to get the field options for users who have MCP clients.
     *
     * @return  array  The field option objects.
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function getOptions()
    {
        $options = [];

        $db    = $this->getDatabase();
        $query = $db->createQuery();

        // Select distinct usernames from MCP table
        $query->select(
            [
                'DISTINCT ' . $db->quoteName('username', 'value'),
                $db->quoteName('username', 'text'),
            ]
        )
            ->from($db->quoteName('#__mcp'))
            ->where($db->quoteName('username') . ' != ' . $db->quote(''))
            ->order($db->quoteName('username') . ' ASC');

        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (\RuntimeException $e) {
            $options = [];
        }

        // Merge with any options from the XML (including "None" and "By Me")
        return array_merge(parent::getOptions(), $options);
    }
}
