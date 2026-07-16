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
        $list       = $operations[0];

        self::assertSame('author', $list->queryParameters['filter[author]']['argument']);
        self::assertSame('ordering', $list->queryParameters['list[ordering]']['argument']);
        self::assertSame('direction', $list->queryParameters['list[direction]']['argument']);
        self::assertArrayNotHasKey('author', $list->queryParameters);
    }

    public function testEveryListParameterIsOptional(): void
    {
        // The webservices controller reads every list parameter conditionally, so none is mandatory. Ordering and
        // direction must not be required either, even though they carry a default.
        $list = (new OperationCompiler())->compile(ArticlesController::class)[0];

        self::assertArrayHasKey('ordering', $list->inputSchema['properties']);
        self::assertArrayHasKey('direction', $list->inputSchema['properties']);
        self::assertSame([], $list->inputSchema['required'] ?? []);
    }

    public function testPaginationIsAddedGenericallyRatherThanByTheQueryDto(): void
    {
        // Pagination is a framework concern the compiler adds to every list operation, so it appears in the tool
        // input and transport even though ArticleListQuery does not declare limit or offset itself.
        $queryProperties = (new \ReflectionClass(
            \Joomla\Component\Content\Api\Query\ArticleListQuery::class,
        ))->getProperties();

        self::assertNotContains('limit', array_map(static fn ($p) => $p->getName(), $queryProperties));

        $list = (new OperationCompiler())->compile(ArticlesController::class)[0];

        self::assertArrayHasKey('limit', $list->inputSchema['properties']);
        self::assertArrayHasKey('offset', $list->inputSchema['properties']);
        self::assertSame('limit', $list->queryParameters['page[limit]']['argument']);
        self::assertSame('offset', $list->queryParameters['page[offset]']['argument']);
    }

    public function testUpdateOperationCombinesIdentifierAndResourceProperties(): void
    {
        $operations = (new OperationCompiler())->compile(ArticlesController::class);
        $update     = $operations[3];

        self::assertSame('PATCH', $update->method);
        self::assertSame('v1/content/articles/:id', $update->path);
        self::assertSame(['id'], $update->inputSchema['required']);
        self::assertArrayHasKey('title', $update->inputSchema['properties']);
        self::assertSame('core.edit', $update->acl['action']);
        self::assertFalse($update->annotations['readOnlyHint']);
    }
}
