<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Resource;

use Joomla\CMS\WebService\Operation\OperationType;
use Joomla\Component\Content\Api\Definition\Resource\Article;
use PHPUnit\Framework\TestCase;

final class ResourceHydratorTest extends TestCase
{
    public function testCreateHydrationRetainsDefaults(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['title' => 'Example', 'catid' => 2],
            OperationType::CREATE,
        );

        self::assertSame('', $article->alias);
        self::assertSame('*', $article->language);
    }

    public function testUpdateHydrationPreservesPresenceSemantics(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['title' => 'Changed', 'note' => null],
            OperationType::UPDATE,
        );

        self::assertTrue($article->has('title'));
        self::assertTrue($article->has('note'));
        self::assertNull($article->note);
        self::assertFalse($article->has('alias'));
    }

    public function testUpdateHydrationRejectsGuardedProperties(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Article::fromArray(['id' => 4], OperationType::UPDATE);
    }

    public function testAdditionalPropertiesArePreserved(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['custom_colour' => 'blue'],
            OperationType::UPDATE,
        );

        self::assertSame('blue', $article->getAdditionalProperties()['custom_colour']);
        self::assertSame('blue', $article->toArray(OperationType::UPDATE)['custom_colour']);
    }
}
