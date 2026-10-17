<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Internal;

use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Dispatcher\WebserviceDispatcher;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationInput;
use PHPUnit\Framework\TestCase;

final class ComponentApiDispatcherTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function sourceFor(int $operationIndex, OperationInput $input): array
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[$operationIndex];
        $method    = new \ReflectionMethod(WebserviceDispatcher::class, 'requestSource');

        return $method->invoke(
            new WebserviceDispatcher(),
            $operation,
            $input,
            'com_content',
        );
    }

    public function testTheTaskReachesTheControllerUnprefixed(): void
    {
        $source = $this->sourceFor(0, new OperationInput([], [], []));

        // BaseController::execute() matches against its task map. A "articles.displayList" task misses it and falls
        // back to __default, which is display(), rather than running the operation.
        self::assertSame('articles', $source['controller']);
        self::assertSame('displayList', $source['task']);
    }

    public function testTheUpdateTaskIsAlsoUnprefixed(): void
    {
        $source = $this->sourceFor(3, new OperationInput(['id' => 7], [], ['title' => 'Changed']));

        self::assertSame('articles', $source['controller']);
        self::assertSame('edit', $source['task']);
        self::assertSame(7, $source['id']);
        self::assertSame(['title' => 'Changed'], $source['data']);
    }

    public function testTheComponentAndFormatAreDeclared(): void
    {
        $source = $this->sourceFor(0, new OperationInput([], [], []));

        self::assertSame('com_content', $source['option']);
        self::assertSame('jsonapi', $source['format']);
    }
}
