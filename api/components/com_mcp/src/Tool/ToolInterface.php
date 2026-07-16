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

use Joomla\Component\MCP\Api\Core\McpRequestContext;
use Mcp\Types\CallToolResult;

/**
 * Interface for MCP tools executed under an authenticated request context.
 *
 * @since  __DEPLOY_VERSION__
 */
interface ToolInterface
{
    /**
     * Returns the tool name.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getName(): string;

    /**
     * Returns the MCP tool schema.
     *
     * @return  array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getSchema(): array;

    /**
     * Executes the tool under the authenticated MCP request context.
     *
     * @param  array<string, mixed>  $params  Tool arguments.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function execute(array $params, McpRequestContext $context): CallToolResult;
}
