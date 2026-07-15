<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\WebService\Internal\InternalApiDispatcherInterface;
use Joomla\CMS\WebService\Internal\InternalApiResponse;
use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\CMS\WebService\Operation\OperationInput;
use Joomla\Component\MCP\Api\Tool\InternalApiOperationInvoker;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\Component\MCP\Api\Tool\InternalApiOperationInvoker
 */
final class InternalApiOperationInvokerTest extends TestCase
{
    public function testItMapsArgumentsAndNormalisesJsonApiData(): void
    {
        $operation = new OperationDefinition(
            operationId: 'content.articles.update',
            method: 'PATCH',
            path: 'v1/content/articles/:id',
            controller: 'articles',
            task: 'edit',
            title: 'Update article',
            description: 'Updates an article.',
            inputSchema: ['type' => 'object'],
            outputSchema: ['type' => 'object'],
            requestBodySchema: [
                'type'       => 'object',
                'properties' => [
                    'category' => ['type' => 'integer', 'x-joomla-source' => 'catid'],
                ],
            ],
            pathParameters: [
                'id' => ['argument' => 'id', 'schema' => ['type' => 'integer']],
            ],
            acl: ['component' => 'com_content'],
        );

        $dispatcher = new class () implements InternalApiDispatcherInterface {
            public ?OperationInput $input = null;

            public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse
            {
                $this->input = $input;

                return new InternalApiResponse(
                    200,
                    [
                        'data' => [
                            'type'       => 'articles',
                            'id'         => '42',
                            'attributes' => ['title' => 'Changed'],
                        ],
                    ],
                );
            }
        };

        $invoker = new InternalApiOperationInvoker(new OperationArgumentMapper(), $dispatcher);
        $result  = $invoker->invoke($operation, ['id' => 42, 'category' => 7]);

        self::assertSame(['id' => 42], $dispatcher->input?->path);
        self::assertSame(['catid' => 7], $dispatcher->input?->body);
        self::assertSame(['title' => 'Changed', 'id' => 42], $result->body);
    }
}
