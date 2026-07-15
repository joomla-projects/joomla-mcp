<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Methods supporting a list of MCP records.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpsModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'client_name', 'a.client_name',
                'username', 'a.username',
                'user_id', 'a.user_id',
                'created', 'a.created',
                'state', 'a.state',
                'published',
            ];
        }

        parent::__construct($config);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  QueryInterface
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function getListQuery()
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                [
                    $db->quoteName('a.id'),
                    $db->quoteName('a.client_name'),
                    $db->quoteName('a.username'),
                    $db->quoteName('a.state'),
                    $db->quoteName('a.ordering'),
                    $db->quoteName('a.created'),
                    $db->quoteName('a.checked_out'),
                    $db->quoteName('a.checked_out_time'),
                ]
            )
        )
            ->from($db->quoteName('#__mcp', 'a'));

        // Get the editor user name
        $query->select($db->quoteName('u.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'u'), $db->quoteName('u.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('a.state') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.state') . ' IN (0, 1)');
        }

        // Filter by search in client_name or username
        if ($search = $this->getState('filter.search')) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('a.id') . ' = :search')
                    ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where('(' . $db->quoteName('a.client_name') . ' LIKE :search1 OR ' . $db->quoteName('a.username') . ' LIKE :search2)')
                    ->bind([':search1', ':search2'], $search);
            }
        }

        // Filter by username
        $username = $this->getState('filter.username');

        if (!empty($username)) {
            // Handle single value
            if (\is_string($username) && $username !== '') {
                $query->where($db->quoteName('a.username') . ' = :username')
                    ->bind(':username', $username);
            } elseif (\is_array($username)) {
                // Remove empty values from array
                $username = array_filter($username, fn ($val) => !empty($val) && \is_string($val));

                if (!empty($username)) {
                    // Quote each username for safe SQL inclusion
                    $quotedUsernames = array_map(fn ($u) => $db->quote($u), $username);
                    $query->where($db->quoteName('a.username') . ' IN (' . implode(',', $quotedUsernames) . ')');
                }
            }
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . serialize($this->getState('filter.username'));

        return parent::getStoreId($id);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        // List state information.
        parent::populateState($ordering, $direction);
    }
}
