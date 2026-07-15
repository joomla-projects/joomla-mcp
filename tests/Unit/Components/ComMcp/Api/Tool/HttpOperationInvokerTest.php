<?php

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Component\MCP\Api\Tool\HttpOperationInvoker;
use Joomla\Component\MCP\Api\Tool\OperationResult;
use PHPUnit\Framework\TestCase;

final class HttpOperationInvokerTest extends TestCase
{
    public function testUpdateIsSentToTheEstablishedRestEndpoint(): void
    {
        $request   = null;
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $invoker   = new HttpOperationInvoker(
            new OperationArgumentMapper(),
            'https://example.test/api/index.php/',
            static fn (): string => 'test-token',
            static function (
                string $method,
                string $url,
                array $headers,
                ?string $body,
                int $timeout,
            ) use (&$request): OperationResult {
                $request = compact('method', 'url', 'headers', 'body', 'timeout');

                return new OperationResult(
                    200,
                    ['data' => ['id' => '7', 'attributes' => ['title' => 'Changed title']]],
                    'application/vnd.api+json',
                );
            },
        );

        $result = $invoker->invoke(
            $operation,
            ['id' => 7, 'title' => 'Changed title', 'category' => 2],
        );

        self::assertSame('PATCH', $request['method']);
        self::assertSame('https://example.test/api/index.php/v1/content/articles/7', $request['url']);
        self::assertSame('test-token', $request['headers']['X-Joomla-Token']);
        self::assertSame(
            ['title' => 'Changed title', 'catid' => 2],
            json_decode($request['body'], true, 512, JSON_THROW_ON_ERROR),
        );
        self::assertSame(['title' => 'Changed title', 'id' => 7], $result->body);
    }
}
