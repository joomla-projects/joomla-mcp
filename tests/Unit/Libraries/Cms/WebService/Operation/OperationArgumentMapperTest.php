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

    public function testCreateForwardsCustomFieldsWhenAdditionalPropertiesAreAllowed(): void
    {
        // The resource allows additional properties, so a custom field the caller supplies must reach the body rather
        // than being dropped, the way the webservices accept custom fields on write.
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[2];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['title' => 'X', 'catid' => 2, 'my_custom_field' => 'value'],
        );

        self::assertSame('value', $input->body['my_custom_field']);
    }

    public function testUpdateForwardsCustomFieldsWithoutMovingThePathIdIntoTheBody(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['id' => 7, 'my_custom_field' => 'value'],
        );

        self::assertSame(['id' => 7], $input->path);
        self::assertSame('value', $input->body['my_custom_field']);
        self::assertArrayNotHasKey('id', $input->body);
    }

    public function testPaginationUsesTheJsonApiPageParameterNames(): void
    {
        // Joomla's API controller reads pagination from page[offset] and page[limit], not from filter[] or list[].
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['limit' => 50, 'offset' => 20],
        );

        self::assertSame(
            ['page[limit]' => 50, 'page[offset]' => 20],
            $input->query,
        );
    }

    public function testCreateFillsOmittedFieldsWithTheirDefaults(): void
    {
        // A create behaves like the administrator form: the body is optional and omitted fields fall back to their
        // declared defaults, so a caller can create an article from a title and category alone.
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[2];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['title' => 'Minimal', 'catid' => 2],
        );

        self::assertSame('Minimal', $input->body['title']);
        self::assertSame(2, $input->body['catid']);
        self::assertSame('', $input->body['articletext']);
        self::assertSame('*', $input->body['language']);
    }

    public function testUpdateDoesNotInventOmittedFields(): void
    {
        // A partial update must not fill defaults, or it would overwrite unspecified fields on every patch.
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['id' => 7, 'title' => 'Changed'],
        );

        self::assertSame(['title' => 'Changed'], $input->body);
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

    public function testDateTimeArgumentsAreConvertedToTheStoredUtcFormat(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['modified_start' => '2026-01-17T22:00:00+05:00'],
        );

        self::assertSame(['filter[modified_start]' => '2026-01-17 17:00:00'], $input->query);
    }

    public function testDateTimeArgumentsWithoutAnOffsetAreReadAsUtc(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['modified_end' => '2026-01-17T22:00:00'],
        );

        self::assertSame(['filter[modified_end]' => '2026-01-17 22:00:00'], $input->query);
    }

    public function testDateTimeObjectsAreAccepted(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['modified_start' => new \DateTimeImmutable('2026-01-17 22:00:00', new \DateTimeZone('+05:00'))],
        );

        self::assertSame(['filter[modified_start]' => '2026-01-17 17:00:00'], $input->query);
    }

    public function testNonDateTimeArgumentsAreNotReformatted(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $input     = (new OperationArgumentMapper())->map(
            $operation,
            ['search' => '2026-01-17T22:00:00+05:00'],
        );

        self::assertSame(['filter[search]' => '2026-01-17T22:00:00+05:00'], $input->query);
    }

    public function testAnUnusableDateTimeArgumentIsRejected(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('modified_start');

        (new OperationArgumentMapper())->map($operation, ['modified_start' => 'last friday-ish']);
    }
}
