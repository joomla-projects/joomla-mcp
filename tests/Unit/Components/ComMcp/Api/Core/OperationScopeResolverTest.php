<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Core;

use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\MCP\Api\Core\OperationScopeResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests convention-derived operation scopes.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationScopeResolverTest extends TestCase
{
    /**
     * @return  iterable<string, array{string, string}>
     */
    public static function operationProvider(): iterable
    {
        yield 'list' => ['content.articles.list', 'content.articles:read'];
        yield 'get' => ['content.articles.get', 'content.articles:read'];
        yield 'create' => ['content.articles.create', 'content.articles:write'];
        yield 'update' => ['content.articles.update', 'content.articles:write'];
        yield 'delete' => ['content.articles.delete', 'content.articles:delete'];
        yield 'publish' => ['content.articles.publish', 'content.articles:publish'];
    }

    /**
     * @dataProvider operationProvider
     */
    public function testDerivesScope(string $operationId, string $scope): void
    {
        $operation = new OperationDefinition(
            operationId: $operationId,
            method: 'POST',
            path: 'v1/content/articles',
            controller: 'articles',
            task: 'test',
            title: 'Test',
            description: 'Test operation.',
            inputSchema: ['type' => 'object'],
            outputSchema: null,
        );

        self::assertSame([$scope], (new OperationScopeResolver())->resolve($operation));
    }
}
