<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\Mcp\Tool\InternalApiOperationInvoker;
use Joomla\CMS\WebService\Internal\InternalApiDispatcherInterface;
use Joomla\CMS\WebService\Internal\InternalApiResponse;
use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\CMS\WebService\Operation\OperationInput;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\CMS\Mcp\Tool\InternalApiOperationInvoker
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
                    'catid' => ['type' => 'integer'],
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
        $result  = $invoker->invoke($operation, ['id' => 42, 'catid' => 7]);

        self::assertSame(['id' => 42], $dispatcher->input?->path);
        self::assertSame(['catid' => 7], $dispatcher->input?->body);
        self::assertSame(['title' => 'Changed', 'id' => 42], $result->body);
    }

    public function testItPassesAnErrorBodyThroughUntouched(): void
    {
        // A conflict response also has a data block, but it carries a message rather than a JSON:API resource.
        // Flattening it to its (missing) attributes would drop the message and leave the client with nothing.
        $operation = new OperationDefinition(
            operationId: 'content.articles.delete',
            method: 'DELETE',
            path: 'v1/content/articles/:id',
            controller: 'articles',
            task: 'delete',
            title: 'Delete article',
            description: 'Deletes an article.',
            inputSchema: ['type' => 'object'],
            outputSchema: null,
            pathParameters: ['id' => ['argument' => 'id', 'schema' => ['type' => 'integer']]],
            acl: ['component' => 'com_content'],
        );

        $conflict = [
            'success' => true,
            'data'    => [
                'status'  => 'Conflict',
                'code'    => 409,
                'message' => 'Resource not in state that can be deleted, must be trashed before it can be deleted',
            ],
        ];

        $dispatcher = new class ($conflict) implements InternalApiDispatcherInterface {
            /**
             * @param array<string, mixed> $conflict
             */
            public function __construct(private readonly array $conflict)
            {
            }

            public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse
            {
                return new InternalApiResponse(409, $this->conflict);
            }
        };

        $invoker = new InternalApiOperationInvoker(new OperationArgumentMapper(), $dispatcher);
        $result  = $invoker->invoke($operation, ['id' => 1]);

        self::assertFalse($result->isSuccessful());
        self::assertSame($conflict, $result->body);
    }

    public function testItConvertsDateTimeOutputToRfc3339(): void
    {
        // Joomla stores and returns dates as Y-m-d H:i:s; the schema declares date-time, so the read output is
        // converted to RFC 3339 to match, symmetrically to the argument conversion on the way in.
        $operation = new OperationDefinition(
            operationId: 'content.articles.get',
            method: 'GET',
            path: 'v1/content/articles/:id',
            controller: 'articles',
            task: 'displayItem',
            title: 'Get article',
            description: 'Returns an article.',
            inputSchema: ['type' => 'object'],
            outputSchema: [
                'type'       => 'object',
                'properties' => [
                    'created'      => ['type' => 'string', 'format' => 'date-time'],
                    'publish_up'   => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'publish_down' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'title'        => ['type' => 'string'],
                ],
            ],
            pathParameters: ['id' => ['argument' => 'id', 'schema' => ['type' => 'integer']]],
            acl: ['component' => 'com_content'],
        );

        $dispatcher = new class () implements InternalApiDispatcherInterface {
            public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse
            {
                return new InternalApiResponse(200, [
                    'data' => [
                        'id'         => '3',
                        'attributes' => [
                            'created'      => '2025-05-30 12:00:00',
                            'publish_up'   => null,
                            'publish_down' => '0000-00-00 00:00:00',
                            'title'        => 'Unchanged 2025-05-30 12:00:00',
                        ],
                    ],
                ]);
            }
        };

        $invoker = new InternalApiOperationInvoker(new OperationArgumentMapper(), $dispatcher);
        $body    = $invoker->invoke($operation, ['id' => 3])->body;

        self::assertSame('2025-05-30T12:00:00+00:00', $body['created']);
        self::assertNull($body['publish_up']);
        // Joomla's zero-date sentinel becomes a clean null rather than a bogus date.
        self::assertNull($body['publish_down']);
        // A non-date field that merely contains a date-like substring is left untouched.
        self::assertSame('Unchanged 2025-05-30 12:00:00', $body['title']);
    }

    public function testItConvertsDateTimeInEveryRowOfACollection(): void
    {
        $operation = new OperationDefinition(
            operationId: 'content.articles.list',
            method: 'GET',
            path: 'v1/content/articles',
            controller: 'articles',
            task: 'displayList',
            title: 'List articles',
            description: 'Lists articles.',
            inputSchema: ['type' => 'object'],
            outputSchema: [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => ['created' => ['type' => 'string', 'format' => 'date-time']],
                ],
            ],
            acl: ['component' => 'com_content'],
        );

        $dispatcher = new class () implements InternalApiDispatcherInterface {
            public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse
            {
                return new InternalApiResponse(200, [
                    'data' => [
                        ['id' => '1', 'attributes' => ['created' => '2025-01-07 08:15:00']],
                        ['id' => '2', 'attributes' => ['created' => '2025-02-03 10:05:00']],
                    ],
                ]);
            }
        };

        $invoker = new InternalApiOperationInvoker(new OperationArgumentMapper(), $dispatcher);
        $body    = $invoker->invoke($operation, [])->body;

        self::assertSame('2025-01-07T08:15:00+00:00', $body[0]['created']);
        self::assertSame('2025-02-03T10:05:00+00:00', $body[1]['created']);
    }
}
