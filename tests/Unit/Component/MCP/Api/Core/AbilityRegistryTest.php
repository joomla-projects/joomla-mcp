<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Components.com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Core;

use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\Component\MCP\Api\Core\AbilityRegistry
 */
final class AbilityRegistryTest extends TestCase
{
    public function testItReturnsRegisteredToolsAndNullForUnknownNames(): void
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('example.items.list');

        $registry = new AbilityRegistry();
        $registry->addAbility($tool);

        self::assertSame($tool, $registry->getTool('example.items.list'));
        self::assertSame(['example.items.list' => $tool], $registry->getTools());
        self::assertNull($registry->getTool('example.items.missing'));
    }
}
