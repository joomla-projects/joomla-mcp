<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

use Joomla\Component\MCP\Api\Prompt\PromptInterface;
use Joomla\Component\MCP\Api\Resource\ResourceInterface;
use Joomla\Component\MCP\Api\Resource\ResourceTemplateInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registry for MCP tools, resources, resource templates and prompts.
 *
 * @since  __DEPLOY_VERSION__
 */
final class AbilityRegistry
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @var array<string, ResourceInterface> */
    private array $resources = [];

    /** @var array<string, ResourceTemplateInterface> */
    private array $resourceTemplates = [];

    /** @var array<string, PromptInterface> */
    private array $prompts = [];

    public function addAbility(
        PromptInterface|ResourceInterface|ResourceTemplateInterface|ToolInterface $ability,
    ): void {
        match (true) {
            $ability instanceof PromptInterface           => $this->prompts[$ability->getName()]           = $ability,
            $ability instanceof ResourceInterface         => $this->resources[$ability->getUri()]          = $ability,
            $ability instanceof ResourceTemplateInterface => $this->resourceTemplates[$ability->getName()] = $ability,
            $ability instanceof ToolInterface             => $this->tools[$ability->getName()]             = $ability,
        };
    }

    /**
     * @return array<string, ToolInterface>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return array<string, ResourceInterface>
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    public function getResource(string $uri): ?ResourceInterface
    {
        return $this->resources[$uri] ?? null;
    }

    /**
     * @return array<string, ResourceTemplateInterface>
     */
    public function getResourceTemplates(): array
    {
        return $this->resourceTemplates;
    }

    public function getResourceTemplate(string $name): ?ResourceTemplateInterface
    {
        return $this->resourceTemplates[$name] ?? null;
    }

    /**
     * @return array<string, PromptInterface>
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    public function getPrompt(string $name): ?PromptInterface
    {
        return $this->prompts[$name] ?? null;
    }
}
