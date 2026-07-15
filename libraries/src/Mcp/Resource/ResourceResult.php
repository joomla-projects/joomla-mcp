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
     * @param array $contents  List of content items, each ['uri' => string, 'text' => string, 'mimeType' => string]
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(private array $contents)
    {
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
        return new self([['uri' => $uri, 'text' => $text, 'mimeType' => $mimeType]]);
    }

    /**
     * Get the content items
     *
     * @return array  List of content items, each ['uri' => string, 'text' => string, 'mimeType' => string]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContents(): array
    {
        return $this->contents;
    }
}
