<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Operation;

use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use PHPUnit\Framework\TestCase;

final class OperationArgumentMapperTest extends TestCase
{
    public function testUpdateArgumentsAreSplitIntoPathAndBody(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['id' => 7, 'title' => 'Changed title'],
        );

        self::assertSame(['id' => 7], $input->path);
        self::assertSame([], $input->query);
        self::assertSame(['title' => 'Changed title'], $input->body);
    }

    public function testCanonicalResourceNamesMapToEstablishedRestNames(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $input = (new OperationArgumentMapper())->map(
            $operation,
            ['id' => 7, 'category' => 2],
        );

        self::assertSame(['catid' => 2], $input->body);
    }

    public function testListArgumentsUseJoomlaQueryParameterNames(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['author' => 42, 'ordering' => 'created'],
        );

        self::assertSame(
            ['filter[author]' => 42, 'list[ordering]' => 'created'],
            $input->query,
        );
    }
}
