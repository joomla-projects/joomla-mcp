<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Content;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Interface for MCP content items returned by tools.
 *
 * @since  __DEPLOY_VERSION__
 */
interface ContentInterface
{
    /**
     * Get the content type
     *
     * @return  ContentType
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getType(): ContentType;

    /**
     * Get the wire format representation of the content item
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function toArray(): array;
}
