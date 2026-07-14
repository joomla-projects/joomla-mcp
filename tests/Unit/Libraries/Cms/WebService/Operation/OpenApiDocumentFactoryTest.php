<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Operation;

use Joomla\CMS\WebService\OpenApi\OpenApiDocumentFactory;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use PHPUnit\Framework\TestCase;

final class OpenApiDocumentFactoryTest extends TestCase
{
    public function testArticleOperationsProjectToOpenApi(): void
    {
        $operations = (new OperationCompiler())->compile(ArticlesController::class);
        $document = (new OpenApiDocumentFactory())->create($operations);

        self::assertSame('3.1.0', $document['openapi']);
        self::assertSame(
            'content.articles.update',
            $document['paths']['/v1/content/articles/{id}']['patch']['operationId'],
        );
        self::assertSame(
            'integer',
            $document['paths']['/v1/content/articles/{id}']['patch']['parameters'][0]['schema']['type'],
        );
        self::assertArrayHasKey(
            'requestBody',
            $document['paths']['/v1/content/articles']['post'],
        );
    }
}
