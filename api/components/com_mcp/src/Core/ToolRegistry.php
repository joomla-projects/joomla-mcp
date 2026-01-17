<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\MCP\Api\Tool\ToolInterface;

/**
 * Registry for MCP tools
 *
 * @since  __DEPLOY_VERSION__
 */
class ToolRegistry
{
    /**
     * @var ToolInterface[] Registered tools
     *
     * @since  __DEPLOY_VERSION__
     */
    protected array $tools = [];

    public function __construct(iterable $tools)
    {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }
    }

    /**
     * Get all registered tools
     *
     * @return ToolInterface[]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Add a tool to the registry
     *
     * @param ToolInterface $tool
     *
     * @return void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function addTool(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * Get a specific tool by name
     *
     * @param string $name Tool name
     *
     * @return ToolInterface|null
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }
}
