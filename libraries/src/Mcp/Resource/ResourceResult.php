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

use Joomla\CMS\Mcp\Content\ResourceContents;

/**
 * Result of reading an MCP resource.
 *
 * Value object shielding resource implementations from the underlying MCP SDK types;
 * the endpoint converts it to the wire format.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ResourceResult
{
    /**
     * Constructor.
     *
     * @param ResourceContents[] $contents  List of content items
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(private array $contents)
    {
    }

    /**
     * Create a result from one or more content items
     *
     * @param ResourceContents ...$contents  The content items
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function fromContents(ResourceContents ...$contents): self
    {
        return new self(array_values($contents));
    }

    /**
     * Create a result with a single text content item
     *
     * @param string $uri       The resource URI
     * @param string $text      The resource content
     * @param string $mimeType  The MIME type of the content
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function text(string $uri, string $text, string $mimeType = 'text/plain'): self
    {
        return new self([ResourceContents::text($uri, $text, $mimeType)]);
    }

    /**
     * Create a result with a single binary content item
     *
     * @param string $uri       The resource URI
     * @param string $blob      The base64 encoded resource content
     * @param string $mimeType  The MIME type of the content
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function blob(string $uri, string $blob, string $mimeType): self
    {
        return new self([ResourceContents::blob($uri, $blob, $mimeType)]);
    }

    /**
     * Get the content items
     *
     * @return ResourceContents[]  List of content items
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContents(): array
    {
        return $this->contents;
    }
}
