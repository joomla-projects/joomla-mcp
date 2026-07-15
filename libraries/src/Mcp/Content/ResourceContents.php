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
 * Contents of a single resource, either text or binary.
 *
 * Used as item of a resources/read result and as payload of an embedded resource
 * content item; the named constructors ensure exactly one of text or blob is set.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ResourceContents
{
    /**
     * Constructor.
     *
     * @param string  $uri       The resource URI
     * @param ?string $text      The text content, null for binary resources
     * @param ?string $blob      The base64 encoded binary content, null for text resources
     * @param ?string $mimeType  Optional MIME type of the content
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(
        public string $uri,
        public ?string $text,
        public ?string $blob,
        public ?string $mimeType
    ) {
    }

    /**
     * Create text resource contents
     *
     * @param string  $uri       The resource URI
     * @param string  $text      The text content
     * @param ?string $mimeType  Optional MIME type of the content
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function text(string $uri, string $text, ?string $mimeType = null): self
    {
        return new self($uri, $text, null, $mimeType);
    }

    /**
     * Create binary resource contents
     *
     * @param string  $uri       The resource URI
     * @param string  $blob      The base64 encoded binary content
     * @param ?string $mimeType  Optional MIME type of the content
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function blob(string $uri, string $blob, ?string $mimeType = null): self
    {
        return new self($uri, null, $blob, $mimeType);
    }

    /**
     * Get the wire format representation of the resource contents
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function toArray(): array
    {
        $data = ['uri' => $this->uri];

        if ($this->text !== null) {
            $data['text'] = $this->text;
        }

        if ($this->blob !== null) {
            $data['blob'] = $this->blob;
        }

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
