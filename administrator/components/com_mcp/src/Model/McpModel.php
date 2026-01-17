<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Model;

defined('_JEXEC') or die;

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
     * @since   __DEPLOY_VERSION__
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
     * @since   1.6
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
     * Method to allow derived classes to preprocess the form.
     */
    protected function preprocessForm(\Joomla\CMS\Form\Form $form, $data, $group = 'mcp')
    {
        parent::preprocessForm($form, $data, $group);

        // Sortierliste für das Feld "ordering" generieren
        $db = $this->getDatabase();
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
