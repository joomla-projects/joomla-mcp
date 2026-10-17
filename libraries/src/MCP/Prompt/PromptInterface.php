<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MCP\Prompt;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Interface for all MCP prompts
 *
 * @since  __DEPLOY_VERSION__
 */
interface PromptInterface
{
    /**
     * Get the prompt name
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getName(): string;

    /**
     * Get the prompt description
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getDescription(): string;

    /**
     * Get the prompt title
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getTitle(): string;

    /**
     * Get the prompt arguments
     *
     * Each argument is an array in the MCP wire format with the keys "name", and optionally
     * "description" and "required", e.g. ['name' => 'id', 'description' => 'Article ID', 'required' => true].
     *
     * @return  array[]
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getArguments(): array;

    /**
     * Get the prompt messages for the given arguments
     *
     * Required arguments are checked by the endpoint before this method is called.
     *
     * @param   array<string, string>  $arguments  The prompt arguments
     *
     * @return  PromptResult
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getMessages(array $arguments): PromptResult;
}
