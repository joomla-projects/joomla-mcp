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
 * Interface for all MCP resource templates
 *
 * @since  __DEPLOY_VERSION__
 */
interface ResourceTemplateInterface
{
    /**
     * Get the resource template name
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getName(): string;

    /**
     * Get the URI template
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getUriTemplate(): string;

    /**
     * Get the resource template description
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getDescription(): string;

    /**
     * Get the resource template title
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
}
