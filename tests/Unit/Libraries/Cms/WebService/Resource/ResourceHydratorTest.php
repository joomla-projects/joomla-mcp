<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Resource;

use Joomla\CMS\WebService\Resource\ResourceProfile;
use Joomla\Component\Content\Api\Resource\Article;
use PHPUnit\Framework\TestCase;

final class ResourceHydratorTest extends TestCase
{
    public function testCreateHydrationRetainsDefaults(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['title' => 'Example', 'category' => 2],
            ResourceProfile::CREATE,
        );

        self::assertSame('', $article->alias);
        self::assertSame('*', $article->language);
    }

    public function testUpdateHydrationPreservesPresenceSemantics(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['title' => 'Changed', 'note' => null],
            ResourceProfile::UPDATE,
        );

        self::assertTrue($article->has('title'));
        self::assertTrue($article->has('note'));
        self::assertNull($article->note);
        self::assertFalse($article->has('alias'));
    }

    public function testUpdateHydrationRejectsGuardedProperties(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Article::fromArray(['id' => 4], ResourceProfile::UPDATE);
    }

    public function testAdditionalPropertiesArePreserved(): void
    {
        /** @var Article $article */
        $article = Article::fromArray(
            ['custom_colour' => 'blue'],
            ResourceProfile::UPDATE,
        );

        self::assertSame('blue', $article->getAdditionalProperties()['custom_colour']);
        self::assertSame('blue', $article->toArray(ResourceProfile::UPDATE)['custom_colour']);
    }
}
