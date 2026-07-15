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
 * Content item linking to a resource the client may fetch via resources/read.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ResourceLink implements ContentInterface
{
    /**
     * Constructor.
     *
     * @param string  $uri          The resource URI
     * @param string  $name         The resource name
     * @param ?string $description  Optional description of the resource
     * @param ?string $mimeType     Optional MIME type of the resource
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null
    ) {
    }

    /**
     * Get the content type
     *
     * @return  ContentType
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getType(): ContentType
    {
        return ContentType::ResourceLink;
    }

    /**
     * Get the wire format representation of the content item
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public function toArray(): array
    {
        $data = ['type' => $this->getType()->value, 'uri' => $this->uri, 'name' => $this->name];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
