<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Layout\LayoutHelper;

/** @var \Joomla\Component\MCP\Administrator\View\Mcps\HtmlView $this */

$displayData = [
    'textPrefix' => 'COM_MCP',
    'formURL'    => 'index.php?option=com_mcp&view=mcps',
    'helpURL'    => '',
    'icon'       => 'icon-cog mcp',
];

$user = $this->getCurrentUser();

if ($user->authorise('core.create', 'com_mcp')) {
    $displayData['createURL'] = 'index.php?option=com_mcp&task=mcp.add';
}

echo LayoutHelper::render('joomla.content.emptystate', $displayData);
