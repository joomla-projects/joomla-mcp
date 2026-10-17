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

/**
 * Result of getting an MCP prompt.
 *
 * Value object shielding prompt implementations from the underlying MCP SDK types;
 * the endpoint converts it to the wire format.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class PromptResult
{
    /**
     * Constructor.
     *
     * @param PromptMessage[] $messages     List of prompt messages
     * @param ?string         $description  Optional description of the rendered prompt
     *
     * @since  __DEPLOY_VERSION__
     */
    private function __construct(private array $messages, private ?string $description = null)
    {
    }

    /**
     * Create a result from one or more messages
     *
     * @param PromptMessage ...$messages  The prompt messages
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function fromMessages(PromptMessage ...$messages): self
    {
        return new self(array_values($messages));
    }

    /**
     * Create a result with a single user text message
     *
     * @param string $text  The message text
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function text(string $text): self
    {
        return new self([PromptMessage::user($text)]);
    }

    /**
     * Get a copy of the result with the given description
     *
     * @param string $description  The description of the rendered prompt
     *
     * @return self
     *
     * @since  __DEPLOY_VERSION__
     */
    public function withDescription(string $description): self
    {
        return new self($this->messages, $description);
    }

    /**
     * Get the prompt messages
     *
     * @return PromptMessage[]  List of prompt messages
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get the description of the rendered prompt
     *
     * @return ?string
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
