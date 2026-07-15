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
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Mcp\Prompt\PromptInterface;
use Joomla\CMS\Mcp\Resource\ResourceInterface;
use Joomla\CMS\Mcp\Resource\ResourceTemplateInterface;
use Joomla\CMS\Mcp\Tool\ToolInterface;
use Joomla\Component\MCP\Api\Exception\AbilityNotFoundException;
use function defined;

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
     * @param string $name  The tool name
     *
     * @return ToolInterface
     *
     * @throws AbilityNotFoundException  If no tool is registered under the given name
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTool(string $name): ToolInterface
    {
        return $this->tools[$name]
            ?? throw new AbilityNotFoundException('Tool not found: ' . $name);
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
     * @param string $uri  The resource URI
     *
     * @return ResourceInterface
     *
     * @throws AbilityNotFoundException  If no resource is registered under the given URI
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResource(string $uri): ResourceInterface
    {
        return $this->resources[$uri]
            ?? throw new AbilityNotFoundException('Resource not found: ' . $uri);
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
     * @param string $name  The resource template name
     *
     * @return ResourceTemplateInterface
     *
     * @throws AbilityNotFoundException  If no resource template is registered under the given name
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResourceTemplate(string $name): ResourceTemplateInterface
    {
        return $this->resourceTemplates[$name]
            ?? throw new AbilityNotFoundException('Resource template not found: ' . $name);
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
     * @param string $name  The prompt name
     *
     * @return PromptInterface
     *
     * @throws AbilityNotFoundException  If no prompt is registered under the given name
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getPrompt(string $name): PromptInterface
    {
        return $this->prompts[$name]
            ?? throw new AbilityNotFoundException('Prompt not found: ' . $name);
    }
}
