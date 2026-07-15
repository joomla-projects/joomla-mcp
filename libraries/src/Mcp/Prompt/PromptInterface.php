<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Prompt;

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
     * Get the prompt URI
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getUri(): string;

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
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getArguments(): array;
}
