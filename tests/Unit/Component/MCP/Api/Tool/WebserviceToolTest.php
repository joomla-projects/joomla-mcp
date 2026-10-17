<?php

namespace Joomla\Tests\Unit\Component\MCP\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Dispatcher\WebserviceDispatcher;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationInput;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResult;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\WebserviceTool;
use PHPUnit\Framework\TestCase;

final class WebserviceToolTest extends TestCase
{
    private function dispatcherReturning(OperationResult $result): WebserviceDispatcher
    {
        return new class ($result) extends WebserviceDispatcher {
            public function __construct(private readonly OperationResult $result)
            {
            }

            public function dispatch(OperationDefinition $operation, OperationInput $input): OperationResult
            {
                return $this->result;
            }
        };
    }

    private function tool(int $operationIndex, OperationResult $result): WebserviceTool
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[$operationIndex];

        return new WebserviceTool($operation, $this->dispatcherReturning($result));
    }

    public function testACollectionReportsAnObjectOutputSchema(): void
    {
        // MCP requires an object at the top level of an output schema. A bare array makes a client reject the
        // whole tool list, not just this tool.
        $schema = $this->tool(0, new OperationResult(200, []))->getSchema()['outputSchema'];

        self::assertSame('object', $schema['type']);
        self::assertSame('array', $schema['properties']['items']['type']);
        self::assertSame(['items'], $schema['required']);
    }

    public function testASingleResourceReportsItsSchemaUnchanged(): void
    {
        $schema = $this->tool(1, new OperationResult(200, []))->getSchema()['outputSchema'];

        self::assertSame('object', $schema['type']);
        self::assertArrayNotHasKey('items', $schema['properties']);
    }

    public function testCollectionRowsAreReportedUnderTheSchemaKey(): void
    {
        $rows   = [['id' => 1], ['id' => 2]];
        $result = $this->tool(0, new OperationResult(200, $rows))->execute([]);

        self::assertFalse($result->isError());
        self::assertSame(['items' => $rows], $result->getStructuredContent());
    }

    public function testASingleResourceIsReportedAsIs(): void
    {
        $article = ['id' => 7, 'title' => 'Example'];
        $result  = $this->tool(1, new OperationResult(200, $article))->execute([]);

        self::assertSame($article, $result->getStructuredContent());
    }

    public function testAFailedCallReportsNoStructuredContent(): void
    {
        $result = $this->tool(0, new OperationResult(500, ['errors' => [['title' => 'Boom']]]))->execute([]);

        self::assertTrue($result->isError());
        self::assertNull($result->getStructuredContent());
    }
}
