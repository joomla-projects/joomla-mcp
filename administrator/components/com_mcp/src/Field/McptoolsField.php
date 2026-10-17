<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Field;

use Joomla\CMS\Event\MCP\InitialiseMCPServerEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Event\DispatcherInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * List of tools registered by enabled MCP plugins.
 *
 * @since  __DEPLOY_VERSION__
 */
class McptoolsField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    protected $type = 'Mcptools';

    /**
     * Get the registered tools as list options.
     *
     * @return  array
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function getOptions()
    {
        $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
        $registry   = new AbilityRegistry();

        PluginHelper::importPlugin('mcp', null, true, $dispatcher);
        $event = new InitialiseMCPServerEvent($registry);
        $dispatcher->dispatch($event->getName(), $event);

        $tools   = $registry->getTools();
        $options = parent::getOptions();
        ksort($tools);

        foreach ($tools as $name => $tool) {
            $options[] = HTMLHelper::_('select.option', $name, $name);
        }

        return $options;
    }
}
