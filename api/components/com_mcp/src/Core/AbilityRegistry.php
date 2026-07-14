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

use Joomla\Component\MCP\Api\Prompt\PromptInterface;
use Joomla\Component\MCP\Api\Resource\ResourceInterface;
use Joomla\Component\MCP\Api\Resource\ResourceTemplateInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;

/**
 * Registry for MCP tools
 *
 * @since  __DEPLOY_VERSION__
 */
class AbilityRegistry
{
    /**
     * @var ToolInterface[] Registered tools
     *
     * @since  __DEPLOY_VERSION__
     */
    protected array $tools = [];

    /**
     * @var ResourceInterface[] Registered resources
     *
     * @since  __DEPLOY_VERSION__
     */
    protected array $resources = [];

    /**
     * @var ResourceTemplateInterface[] Registered resources
     *
     * @since  __DEPLOY_VERSION__
     */
    protected array $resourceTemplates = [];

    /**
     * @var PromptInterface[] Registered resources
     *
     * @since  __DEPLOY_VERSION__
     */
    protected array $prompts = [];

    public function __construct()
    {
    }

    public function addAbility(PromptInterface|ResourceInterface|ResourceTemplateInterface|ToolInterface $ability): void
    {
        switch (true) {
            case $ability instanceof PromptInterface:
                $this->prompts[$ability->getName()] = $ability;
                break;
            case $ability instanceof ResourceInterface:
                $this->resources[$ability->getUri()] = $ability;
                break;
            case $ability instanceof ResourceTemplateInterface:
                $this->resourceTemplates[$ability->getName()] = $ability;
                break;
            case $ability instanceof ToolInterface:
                $this->tools[$ability->getName()] = $ability;
                break;
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
     * Get a registered tool by name
     *
     * @return ToolInterface|null
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered resources
     *
     * @return ResourceInterface[]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * Get a registered resource by URI
     *
     * @return ResourceInterface|null
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResource(string $uri): ?ResourceInterface
    {
        return $this->resources[$uri] ?? null;
    }

    /**
     * Get all registered resourceTemplates
     *
     * @return ResourceTemplateInterface[]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResourceTemplates(): array
    {
        return $this->resourceTemplates;
    }

    /**
     * Get a registered resource template by name
     *
     * @return ResourceTemplateInterface|null
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResourceTemplate(string $name): ?ResourceTemplateInterface
    {
        return $this->resourceTemplates[$name] ?? null;
    }

    /**
     * Get all registered resourceTemplates
     *
     * @return PromptInterface[]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    /**
     * Get a registered prompt by name
     *
     * @return PromptInterface|null
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getPrompt(string $name): ?PromptInterface
    {
        return $this->prompts[$name] ?? null;
    }
}
