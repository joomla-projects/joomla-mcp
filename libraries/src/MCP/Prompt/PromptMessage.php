<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MCP\Prompt;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MCP\Content\ContentInterface;
use Joomla\CMS\MCP\Content\ContentType;
use Joomla\CMS\MCP\Content\TextContent;

/**
 * A single message of an MCP prompt.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class PromptMessage
{
    /**
     * Constructor.
     *
     * @param string           $role     The message role, "user" or "assistant"
     * @param ContentInterface $content  The message content
     *
     * @throws \InvalidArgumentException  If the content type is not allowed in prompt messages
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(private string $role, private ContentInterface $content)
    {
        // The MCP spec allows text, image, audio and embedded resources in prompt messages, but no resource links
        if ($content->getType() === ContentType::ResourceLink) {
            throw new \InvalidArgumentException('Resource links are not allowed in prompt messages');
        }
    }

    /**
     * Create a user message
     *
     * @param ContentInterface|string $content  The message content, a string creates a text content item
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function user(ContentInterface|string $content): self
    {
        return new self('user', \is_string($content) ? new TextContent($content) : $content);
    }

    /**
     * Create an assistant message
     *
     * @param ContentInterface|string $content  The message content, a string creates a text content item
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function assistant(ContentInterface|string $content): self
    {
        return new self('assistant', \is_string($content) ? new TextContent($content) : $content);
    }

    /**
     * Get the message role
     *
     * @return string  "user" or "assistant"
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Get the message content
     *
     * @return ContentInterface
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContent(): ContentInterface
    {
        return $this->content;
    }

    /**
     * Get the wire format representation of the message
     *
     * @return array
     *
     * @since  __DEPLOY_VERSION__
     */
    public function toArray(): array
    {
        return [
            'role'    => $this->role,
            'content' => $this->content->toArray(),
        ];
    }
}
