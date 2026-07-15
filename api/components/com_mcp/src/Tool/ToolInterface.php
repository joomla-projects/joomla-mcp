<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Tool;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Mcp\Types\CallToolResult;

/**
 * Interface for all MCP tools
 *
 * @since  __DEPLOY_VERSION__
 */
interface ToolInterface
{
    /**
     * Get the tool name
     *
     * @return string
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getName(): string;

    /**
     * Get the tool schema (JSON Schema format)
     *
     * @return array
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getSchema(): array;

    /**
     * Execute the tool with the given parameters
     *
     * @param array $params  The tool parameters
     *
     * @return CallToolResult  The tool result
     *
     * @since  __DEPLOY_VERSION__
     */
    public function execute(array $params): CallToolResult;
}
