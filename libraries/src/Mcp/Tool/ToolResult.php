<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Tool;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Result of an MCP tool execution.
 *
 * Value object shielding tool implementations from the underlying MCP SDK types;
 * the endpoint converts it to the wire format.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ToolResult
{
    /**
     * Content type for plain text items
     *
     * @since  __DEPLOY_VERSION__
     */
    public const TYPE_TEXT = 'text';

    /**
     * Constructor.
     *
     * @param array   $content  List of content items, each ['type' => self::TYPE_*, ...]
     * @param boolean $error    Whether the result represents an error
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(
        private readonly array $content,
        private readonly bool $error
    ) {
    }

    /**
     * Create a successful text result
     *
     * @param string $text  The result text
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function text(string $text): self
    {
        return new self([['type' => self::TYPE_TEXT, 'text' => $text]], false);
    }

    /**
     * Create an error text result
     *
     * @param string $text  The error message
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function error(string $text): self
    {
        return new self([['type' => self::TYPE_TEXT, 'text' => $text]], true);
    }

    /**
     * Get the content items
     *
     * @return array  List of content items, each ['type' => self::TYPE_*, ...]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Whether the result represents an error
     *
     * @return boolean
     *
     * @since  __DEPLOY_VERSION__
     */
    public function isError(): bool
    {
        return $this->error;
    }
}
