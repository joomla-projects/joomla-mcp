<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Resource;

use Joomla\CMS\WebService\Definition\Resource\Schema\SchemaFactory;
use Joomla\CMS\WebService\Operation\OperationType;
use Joomla\Component\Content\Api\Definition\Resource\Article;
use PHPUnit\Framework\TestCase;

final class ResourceSchemaFactoryTest extends TestCase
{
    public function testCreateSchemaUsesDefaultsAndGuardedConvention(): void
    {
        $schema = (new SchemaFactory())->create(Article::class, OperationType::CREATE);

        // The body is optional, as it is in the administrator, so only the title and category are required.
        self::assertSame(['title', 'catid'], $schema['required']);
        self::assertArrayNotHasKey('id', $schema['properties']);
        // The body is addressed as articletext on write and text on read; neither leaks into the other profile.
        self::assertArrayHasKey('articletext', $schema['properties']);
        self::assertArrayNotHasKey('text', $schema['properties']);
        self::assertSame('*', $schema['properties']['language']['default']);
        self::assertSame('integer', $schema['properties']['tags']['items']['type']);
        self::assertTrue($schema['additionalProperties']);
    }

    public function testUpdateSchemaIsPartialAndPreservesNullableSemantics(): void
    {
        $schema = (new SchemaFactory())->create(Article::class, OperationType::UPDATE);

        self::assertArrayNotHasKey('required', $schema);
        self::assertSame(1, $schema['minProperties']);
        self::assertArrayNotHasKey('modified', $schema['properties']);
        self::assertContains('null', $schema['properties']['note']['type']);
    }

    public function testListSchemaHonoursProfileVisibility(): void
    {
        $schema = (new SchemaFactory())->create(Article::class, OperationType::LIST);

        self::assertArrayNotHasKey('modified_by', $schema['properties']);
        self::assertArrayHasKey('modified_by', (new SchemaFactory())->create(
            Article::class,
            OperationType::READ,
        )['properties']);
    }

    public function testReadSchemaContainsGuardedProperties(): void
    {
        $schema = (new SchemaFactory())->create(Article::class, OperationType::READ);

        self::assertTrue($schema['properties']['id']['readOnly']);
        self::assertSame('object', $schema['properties']['tags']['items']['type']);
        self::assertSame('date-time', $schema['properties']['created']['format']);
        // Read exposes the combined body as text; the write-only articletext is absent.
        self::assertArrayHasKey('text', $schema['properties']);
        self::assertArrayNotHasKey('articletext', $schema['properties']);
    }

    public function testCreationDateIsSettableOnCreateAndUpdate(): void
    {
        $factory = new SchemaFactory();

        // The administrator lets the creation date be set on create and edited afterwards, so it is writable in both
        // without being demanded on either.
        $create = $factory->create(Article::class, OperationType::CREATE);
        self::assertArrayHasKey('created', $create['properties']);
        self::assertNotContains('created', $create['required'] ?? []);

        $update = $factory->create(Article::class, OperationType::UPDATE);
        self::assertArrayHasKey('created', $update['properties']);
        self::assertNotContains('created', $update['required'] ?? []);
    }

    public function testNestedValueObjectsAreExpandedIntoTheSchema(): void
    {
        $urls = (new SchemaFactory())
            ->create(Article::class, OperationType::CREATE)['properties']['urls'];

        self::assertSame('object', $urls['type']);
        self::assertSame('string', $urls['properties']['targeta']['type']);
        self::assertSame('0', $urls['properties']['targeta']['example']);
        self::assertArrayHasKey('description', $urls['properties']['urla']);
    }

    public function testNestedValueObjectsHonourTheProfileConvention(): void
    {
        $factory = new SchemaFactory();

        // Every article url slot has a default, so a create never demands one.
        $create = $factory->create(Article::class, OperationType::CREATE)['properties']['urls'];
        self::assertArrayHasKey('urla', $create['properties']);
        self::assertArrayNotHasKey('required', $create);

        $update = $factory->create(Article::class, OperationType::UPDATE)['properties']['urls'];
        self::assertArrayHasKey('urla', $update['properties']);
        self::assertArrayNotHasKey('required', $update);
    }

    public function testANestedPropertyWithoutADefaultIsRequiredOnCreateOnly(): void
    {
        $factory = new SchemaFactory();
        $stub    = NestedValueObjectStub::class;

        self::assertSame(['mandatory'], $factory->create($stub, OperationType::CREATE)['required']);

        // A patch is partial, so nothing is mandatory.
        self::assertArrayNotHasKey('required', $factory->create($stub, OperationType::UPDATE));
    }

    public function testCategoryIsWrittenAsAnIdentifierAndReadAsAnObject(): void
    {
        $factory = new SchemaFactory();

        $create = $factory->create(Article::class, OperationType::CREATE)['properties'];
        $read   = $factory->create(Article::class, OperationType::READ)['properties'];

        self::assertSame('integer', $create['catid']['type']);
        self::assertArrayNotHasKey('category', $create);

        self::assertSame('object', $read['category']['type']);
        self::assertArrayNotHasKey('catid', $read);
    }
}
