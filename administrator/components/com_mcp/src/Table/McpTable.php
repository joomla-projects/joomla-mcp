<?php declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

/**
 * MCP Table class.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpTable extends Table implements VersionableTableInterface
{
    use \Joomla\CMS\Tag\TaggableTableTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  __DEPLOY_VERSION__
     */
    protected $_supportNullValue = true;
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $client_name = '';

    /**
     * @var int
     */
    public $user_id = 0;

    /**
     * @var string
     */
    public $username = '';

    /**
     * @var string
     */
    public $client_token = '';

    /**
     * @var string
     */
    public $capabilities = '';

    /**
     * @var int
     */
    public $state = 1;

    /**
     * @var int
     */
    public $ordering = 0;

    /**
     * @var int|null
     */
    public $checked_out;

    /**
     * @var string
     */
    public $checked_out_time;

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
     * @var int
     */
    public $version = 1;

    /**
     * @var string
     */
    public $version_note = '';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database driver object.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->typeAlias = 'com_mcp.mcp';

        parent::__construct('#__mcp', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Get the type alias for UCM features
     *
     * @return  string  The alias as described above
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
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

        foreach (['publish_up', 'publish_down', 'checked_out_time', 'reset', 'created', 'modified'] as $field) {
            if (isset($src[$field]) && ($src[$field] === '' || $src[$field] === Factory::getDbo()->getNullDate())) {
                $src[$field] = null;
            }
        }
        foreach (['version', 'user_id', 'ordering', 'created_by', 'modified_by'] as $field) {
            if (isset($src[$field]) && $src[$field] === '') {
                $src[$field] = 0;
            }
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

        if (!$this->id) {
            if (empty($this->user_id)) {
                $this->user_id = $user->id;
            }

            if (empty($this->username)) {
                $this->username = $user->name;
            }

            if (empty($this->client_token)) {
                $this->client_token = ApplicationHelper::getHash(microtime() . $user->id);
            }

            if (!(int) $this->created) {
                $this->created = Factory::getDate()->toSql();
            }

            if (empty($this->created_by)) {
                $this->created_by = $user->id;
            }
        }

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
            if (empty($this->ordering)) {
                $this->ordering = self::getNextOrder();
            }
        } else {
            // Increment version for existing records
            if (property_exists($this, 'version')) {
                $this->version++;
            }
        }

        return parent::store($updateNulls);
    }

}
