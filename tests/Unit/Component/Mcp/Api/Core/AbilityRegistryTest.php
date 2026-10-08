<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\Mcp\Api\Core;

use Joomla\CMS\Mcp\Tool\ToolInterface;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Test class for the AbilityRegistry.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class AbilityRegistryTest extends UnitTestCase
{
    /**
     * Build a tool mock reporting the given name.
     *
     * @param string $name  The tool name
     *
     * @return ToolInterface
     *
     * @since  __DEPLOY_VERSION__
     */
    private function createTool(string $name): ToolInterface
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn($name);

        return $tool;
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAddAbilityRegistersTool(): void
    {
        $registry = new AbilityRegistry();
        $tool     = $this->createTool('article.get');

        $registry->addAbility($tool);

        $this->assertSame($tool, $registry->getTool('article.get'));
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAddAbilityKeepsDistinctKeys(): void
    {
        $registry = new AbilityRegistry();

        $registry->addAbility($this->createTool('article.get'));
        $registry->addAbility($this->createTool('article.save'));

        $this->assertCount(2, $registry->getTools());
    }

    /**
     * A duplicate key is logged and ignored, the first registration is kept, and unrelated abilities still register.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAddAbilityLogsAndSkipsDuplicateKey(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('article.get'));

        $registry = new AbilityRegistry($logger);
        $first    = $this->createTool('article.get');

        $registry->addAbility($first);
        $registry->addAbility($this->createTool('article.get'));
        $registry->addAbility($this->createTool('article.save'));

        $this->assertSame($first, $registry->getTool('article.get'));
        $this->assertCount(2, $registry->getTools());
    }
}
