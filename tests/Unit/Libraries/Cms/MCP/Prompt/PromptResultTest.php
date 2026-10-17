<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\MCP\Prompt;

use Joomla\CMS\MCP\Content\ImageContent;
use Joomla\CMS\MCP\Content\ResourceLink;
use Joomla\CMS\MCP\Prompt\PromptMessage;
use Joomla\CMS\MCP\Prompt\PromptResult;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for PromptResult and PromptMessage.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class PromptResultTest extends UnitTestCase
{
    /**
     * Get the wire format of all messages of a result
     *
     * @param PromptResult $result  The result to convert
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    private function toWireFormat(PromptResult $result): array
    {
        return array_map(static fn ($message) => $message->toArray(), $result->getMessages());
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTextCreatesSingleUserMessage(): void
    {
        $result = PromptResult::text('Summarise article 1');

        $this->assertSame(
            [['role' => 'user', 'content' => ['type' => 'text', 'text' => 'Summarise article 1']]],
            $this->toWireFormat($result)
        );
        $this->assertNull($result->getDescription());
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFromMessagesKeepsOrderAndRoles(): void
    {
        $result = PromptResult::fromMessages(
            PromptMessage::user('Question'),
            PromptMessage::assistant('Answer'),
            PromptMessage::user(new ImageContent('aW1n', 'image/png'))
        );

        $this->assertSame(
            [
                ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'Question']],
                ['role' => 'assistant', 'content' => ['type' => 'text', 'text' => 'Answer']],
                ['role' => 'user', 'content' => ['type' => 'image', 'data' => 'aW1n', 'mimeType' => 'image/png']],
            ],
            $this->toWireFormat($result)
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testWithDescriptionReturnsCopy(): void
    {
        $result    = PromptResult::text('x');
        $described = $result->withDescription('Rendered prompt');

        $this->assertNull($result->getDescription());
        $this->assertSame('Rendered prompt', $described->getDescription());
        $this->assertSame($this->toWireFormat($result), $this->toWireFormat($described));
    }

    /**
     * Resource links are not allowed in prompt messages by the MCP spec.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testResourceLinkIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PromptMessage::user(new ResourceLink('joomla://articles/1', 'article'));
    }
}
