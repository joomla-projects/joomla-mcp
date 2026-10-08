<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Resource;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Interface for all MCP resources
 *
 * @since  __DEPLOY_VERSION__
 */
interface ResourceInterface
{
    /**
     * Get the resource name
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getName(): string;

    /**
     * Get the resource URI
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getUri(): string;

    /**
     * Get the resource description
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getDescription(): string;

    /**
     * Get the resource title
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getTitle(): string;

    /**
     * Get the MIME type of the resource content
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getMimeType(): string;

    /**
     * Read the resource content
     *
     * @return  ResourceResult  The resource content
     *
     * @since   __DEPLOY_VERSION__
     */
    public function read(): ResourceResult;
}
