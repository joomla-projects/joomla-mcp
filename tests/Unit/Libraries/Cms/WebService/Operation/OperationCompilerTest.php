<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Operation;

use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use PHPUnit\Framework\TestCase;

final class OperationCompilerTest extends TestCase
{
    public function testArticleControllerCompilesFiveCrudOperations(): void
    {
        $operations = (new OperationCompiler())->compile(ArticlesController::class);

        self::assertCount(5, $operations);
        self::assertSame(
            [
                'content.articles.list',
                'content.articles.get',
                'content.articles.create',
                'content.articles.update',
                'content.articles.delete',
            ],
            array_column($operations, 'operationId'),
        );
    }

    public function testListOperationUsesJoomlaQueryStringConventions(): void
    {
        $operations = (new OperationCompiler())->compile(ArticlesController::class);
        $list = $operations[0];

        self::assertSame('author', $list->queryParameters['filter[author]']['argument']);
        self::assertSame('ordering', $list->queryParameters['list[ordering]']['argument']);
        self::assertSame('direction', $list->queryParameters['list[direction]']['argument']);
        self::assertArrayNotHasKey('author', $list->queryParameters);
    }

    public function testUpdateOperationCombinesIdentifierAndResourceProperties(): void
    {
        $operations = (new OperationCompiler())->compile(ArticlesController::class);
        $update = $operations[3];

        self::assertSame('PATCH', $update->method);
        self::assertSame('v1/content/articles/:id', $update->path);
        self::assertSame(['id'], $update->inputSchema['required']);
        self::assertArrayHasKey('title', $update->inputSchema['properties']);
        self::assertSame('core.edit', $update->acl['action']);
        self::assertFalse($update->annotations['readOnlyHint']);
    }
}
