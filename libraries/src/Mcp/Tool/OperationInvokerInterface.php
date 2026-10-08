<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Tool;

use Joomla\CMS\WebService\Operation\OperationDefinition;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Executes a canonical operation for a generic MCP tool.
 *
 * @since  __DEPLOY_VERSION__
 */
interface OperationInvokerInterface
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @since  __DEPLOY_VERSION__
     */
    public function invoke(OperationDefinition $operation, array $arguments): OperationResult;
}
