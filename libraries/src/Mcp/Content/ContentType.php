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
 * MCP content item types, backed by their wire format identifiers.
 *
 * @since  __DEPLOY_VERSION__
 */
enum ContentType: string
{
    case Text         = 'text';
    case Image        = 'image';
    case Audio        = 'audio';
    case Resource     = 'resource';
    case ResourceLink = 'resource_link';
}
