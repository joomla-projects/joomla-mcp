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

use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\MCP\Api\Core\McpRequestContext;

/**
 * Executes a canonical operation for a generic MCP tool.
 *
 * @since  __DEPLOY_VERSION__
 */
interface OperationInvokerInterface
{
    /**
     * @param  array<string, mixed>  $arguments  Operation arguments.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function invoke(
        OperationDefinition $operation,
        array $arguments,
        McpRequestContext $context,
    ): OperationResult;
}
