<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\Mcp\Resource;

use Joomla\CMS\Mcp\Content\ResourceContents;
use Joomla\CMS\Mcp\Resource\ResourceResult;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for ResourceResult.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class ResourceResultTest extends UnitTestCase
{
    /**
     * Get the wire format of all content items of a result
     *
     * @param ResourceResult $result  The result to convert
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    private function toWireFormat(ResourceResult $result): array
    {
        return array_map(static fn ($item) => $item->toArray(), $result->getContents());
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTextCreatesSingleTextItem(): void
    {
        $result = ResourceResult::text('joomla://config', '{"sitename":"Test"}', 'application/json');

        $this->assertCount(1, $result->getContents());
        $this->assertInstanceOf(ResourceContents::class, $result->getContents()[0]);
        $this->assertSame(
            [['uri' => 'joomla://config', 'text' => '{"sitename":"Test"}', 'mimeType' => 'application/json']],
            $this->toWireFormat($result)
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTextDefaultsToPlainTextMimeType(): void
    {
        $result = ResourceResult::text('joomla://info', 'hello');

        $this->assertSame(
            [['uri' => 'joomla://info', 'text' => 'hello', 'mimeType' => 'text/plain']],
            $this->toWireFormat($result)
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testBlobCreatesSingleBlobItem(): void
    {
        $result = ResourceResult::blob('joomla://media/logo.png', 'YmxvYg==', 'image/png');

        $this->assertSame(
            [['uri' => 'joomla://media/logo.png', 'blob' => 'YmxvYg==', 'mimeType' => 'image/png']],
            $this->toWireFormat($result)
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFromContentsAcceptsMultipleItems(): void
    {
        $result = ResourceResult::fromContents(
            ResourceContents::text('joomla://config', '{}', 'application/json'),
            ResourceContents::blob('joomla://media/logo.png', 'YmxvYg==', 'image/png')
        );

        $this->assertSame(
            [
                ['uri' => 'joomla://config', 'text' => '{}', 'mimeType' => 'application/json'],
                ['uri' => 'joomla://media/logo.png', 'blob' => 'YmxvYg==', 'mimeType' => 'image/png'],
            ],
            $this->toWireFormat($result)
        );
    }
}
