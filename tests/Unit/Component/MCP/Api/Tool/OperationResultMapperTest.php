<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResult;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResultMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResultMapper
 */
final class OperationResultMapperTest extends TestCase
{
    public function testItFlattensAJsonApiResource(): void
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

        $raw = new OperationResult(200, [
            'data' => [
                'type'       => 'articles',
                'id'         => '42',
                'attributes' => ['title' => 'Changed'],
            ],
        ]);

        $result = (new OperationResultMapper())->map($operation, $raw);

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

        $result = (new OperationResultMapper())->map($operation, new OperationResult(409, $conflict));

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

        $body = (new OperationResultMapper())->map($operation, new OperationResult(200, [
             'data' => [
                 'id'         => '3',
                 'attributes' => [
                     'created'      => '2025-05-30 12:00:00',
                     'publish_up'   => null,
                     'publish_down' => '0000-00-00 00:00:00',
                     'title'        => 'Unchanged 2025-05-30 12:00:00',
                 ],
             ],
         ]))->body;

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

        $body = (new OperationResultMapper())->map($operation, new OperationResult(200, [
             'data' => [
                 ['id' => '1', 'attributes' => ['created' => '2025-01-07 08:15:00']],
                 ['id' => '2', 'attributes' => ['created' => '2025-02-03 10:05:00']],
             ],
         ]))->body;

        self::assertSame('2025-01-07T08:15:00+00:00', $body[0]['created']);
        self::assertSame('2025-02-03T10:05:00+00:00', $body[1]['created']);
    }

    public function testItReportsAnEmptyObjectAndFillsRequiredDefaults(): void
    {
        // Joomla returns an unset object such as the images of an article as an empty list, and a relationship such as
        // the category outside the attributes. The schema declares an object with required members, and the category.
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
                    'images' => [
                        'type'       => 'object',
                        'properties' => ['image_intro' => ['type' => 'string', 'default' => '']],
                        'required'   => ['image_intro'],
                    ],
                    'extra'    => ['type' => 'object'],
                    'category' => ['type' => 'object'],
                ],
            ],
            acl: ['component' => 'com_content'],
        );

        $body = (new OperationResultMapper())->map($operation, new OperationResult(200, [
            'data' => [
                'id'            => '3',
                'attributes'    => ['images' => [], 'extra' => []],
                'relationships' => ['category' => ['data' => ['type' => 'categories', 'id' => '2']]],
            ],
        ]))->body;

        self::assertSame(['image_intro' => ''], $body['images']);
        self::assertEquals(new \stdClass(), $body['extra']);
        self::assertSame('{}', json_encode($body['extra']));
        self::assertSame(['type' => 'categories', 'id' => 2], $body['category']);
    }
}
