<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Tool;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Ability that declares its required OAuth scopes.
 *
 * @since  __DEPLOY_VERSION__
 */
interface ScopedAbilityInterface
{
    /**
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getRequiredScopes(): array;
}
