<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

/**
 * MCP Model for a single record.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpModel extends AdminModel
{
    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\Table\Table  A \Joomla\CMS\Table\Table object
     *
     * @since  __DEPLOY_VERSION__
     */

    public function getTable($name = 'Mcp', $prefix = 'Administrator', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_mcp.edit.mcp.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }
        $this->preprocessData('com_mcp.mcp', $data);

        return $data;
    }

    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false otherwise.
     *
     * @return  \Joomla\CMS\Form\Form|boolean  A \Joomla\CMS\Form\Form object on success, false on failure
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_mcp.mcp', 'mcp', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Get user object by token
     *
     * @param   string  $token  The client token to search for
     *
     * @return  object|null  The MCP client object or null if not found
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getUserByToken($token): ?object
    {
        if (empty($token)) {
            return null;
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('*')
            ->from($db->quoteName('#__mcp'))
            ->where($db->quoteName('client_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('state') . ' = 1');

        $db->setQuery($query);

        try {
            $result = $db->loadObject();
            return $result ?: null;
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Get an access token from the database by token
     *
     * @param string $token  The token to look up
     * @return array|null  The access token data or null if not found
     * @since __DEPLOY_VERSION__
     */
    public function getByToken(string $token): ?array
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();
        $query->select('*')
            ->from('#__mcp')
            ->where('client_token = ' . $db->quote($token))
            ->where($db->quoteName('state') . ' = 1');
        $db->setQuery($query);

        try {
            $result = $db->loadAssoc();
            return $result ?: null;
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Method to allow derived classes to preprocess the form.
     */
    protected function preprocessForm(\Joomla\CMS\Form\Form $form, $data, $group = 'mcp')
    {
        parent::preprocessForm($form, $data, $group);

        // Sortierliste für das Feld "ordering" generieren
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['ordering AS value', 'client_name AS text'])
            ->from($db->quoteName('#__mcp'))
            ->order($db->quoteName('ordering') . ' ASC');

        $options = $db->setQuery($query)->loadObjectList();

        if ($options) {
            // Wir holen uns das XML-Element des Feldes
            $element = $form->getFieldXml('ordering');

            if ($element) {
                foreach ($options as $option) {
                    // Wir fügen die Optionen direkt dem XML-Element hinzu
                    $element->addChild('option', htmlspecialchars($option->text))
                        ->addAttribute('value', (string) $option->value);
                }
            }
        }
    }


}
