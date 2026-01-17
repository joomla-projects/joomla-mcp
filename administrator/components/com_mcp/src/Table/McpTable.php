<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Date\DateFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * MCP Table class.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpTable extends Table
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name = '';

    /**
     * @var int
     */
    public $user_id = 0;

    /**
     * @var string
     */
    public $user_name = '';

    /**
     * @var string
     */
    public $user_token = '';

    /**
     * @var string
     */
    public $capabilities = '';

    /**
     * @var string
     */
    public $additional_json = '';

    /**
     * @var int
     */
    public $state = 1;

    /**
     * @var int
     */
    public $ordering = 0;

    /**
     * @var string
     */
    public $created;

    /**
     * @var int
     */
    public $created_by;

    /**
     * @var string
     */
    public $modified;

    /**
     * @var int
     */
    public $modified_by;

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database driver object.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__mcp', 'id', $db);
    }

    /**
     * Method to bind an associative array or object to the Table instance.
     */
    public function bind($src, $ignore = [])
    {
        if (isset($src['params']) && \is_array($src['params'])) {
            $registry = new Registry($src['params']);
            $src['params'] = (string) $registry;
        }

        if (!isset($src['params'])) {
            $src['params'] = '{}';
        }

        return parent::bind($src, $ignore);
    }

    /**
     * Method to perform sanity checks and prepare the row for saving.
     *
     * @return  boolean  True if all checks passed.
     */
    /**
     * Method to perform sanity checks and prepare the row for saving.
     *
     * @return  boolean  True if all checks passed.
     */
    public function check()
    {
        $user = Factory::getApplication()->getIdentity();

        // Logik für neue Datensätze
        if (!$this->id) {
            // Automatisch die User-ID des Erstellers setzen
            if (empty($this->user_id)) {
                $this->user_id = $user->id;
            }

            // Automatisch den User-Namen setzen
            if (empty($this->user_name)) {
                $this->user_name = $user->name;
            }

            // Automatisch einen sicheren User-Token generieren
            if (empty($this->user_token)) {
                $this->user_token = ApplicationHelper::getHash(microtime() . $user->id);
            }

            // Standard Joomla Erstellungsdatum (Abwärtskompatible Methode)
            if (!(int) $this->created) {
                $this->created = Factory::getDate()->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }
        }

        // Änderungsdatum und Nutzer bei jedem Speichern aktualisieren
        $this->modified    = Factory::getDate()->toSql();
        $this->modified_by = $user->id;

        return parent::check();
    }

    /**
     * Method to load a row from the database.
     */
    public function load($keys = null, $reset = true)
    {
        if (parent::load($keys, $reset)) {
            if (\is_string($this->params)) {
                $registry = new Registry($this->params);
                $this->params = $registry->toArray();
            }

            return true;
        }

        return false;
    }

    /**
     * Method to store a node in the database table.
     */
    public function store($updateNulls = false)
    {
        $date = Factory::getDate()->toSql();

        if (!$this->id) {
            // Neue Datensätze an das Ende der Sortierung setzen
            if (empty($this->ordering)) {
                $this->ordering = self::getNextOrder();
            }
        }

        return parent::store($updateNulls);
    }
}
