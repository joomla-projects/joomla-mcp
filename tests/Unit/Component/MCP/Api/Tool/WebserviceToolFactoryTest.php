<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Components.com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\MCP\Api\Tool\OperationInvokerInterface;
use Joomla\Component\MCP\Api\Tool\WebserviceTool;
use Joomla\Component\MCP\Api\Tool\WebserviceToolFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\Component\MCP\Api\Tool\WebserviceToolFactory
 */
final class WebserviceToolFactoryTest extends TestCase
{
    public function testItProjectsAnOperationToAGenericTool(): void
    {
        $operation = new OperationDefinition(
            operationId: 'example.items.get',
            method: 'GET',
            path: 'v1/example/items/:id',
            controller: 'items',
            task: 'displayItem',
            title: 'Get item',
            description: 'Returns one item.',
            inputSchema: ['type' => 'object'],
            outputSchema: ['type' => 'object'],
        );
        $factory = new WebserviceToolFactory($this->createMock(OperationInvokerInterface::class));

        $tool = $factory->create($operation);

        self::assertInstanceOf(WebserviceTool::class, $tool);
        self::assertSame('example.items.get', $tool->getName());
    }
}
