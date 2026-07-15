<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\AdditionalProperties;
use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Example;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Resource\Attribute\Property\Items;
use Joomla\CMS\WebService\Resource\Attribute\Property\Optional;
use Joomla\CMS\WebService\Resource\Attribute\Property\Required;
use Joomla\CMS\WebService\Resource\Resource;
use Joomla\CMS\WebService\Resource\ResourceProfile;
use Joomla\Component\Content\Api\Resource\Enum\ArticleState;

/**
 * Canonical article resource contract.
 *
 * Properties without defaults are required when an article is created. Guarded properties are returned to clients but
 * cannot be mass assigned. No property is required for an update because PATCH is partial by convention.
 *
 * @since  __DEPLOY_VERSION__
 */
#[AdditionalProperties]
final class Article extends Resource
{
    #[Guarded]
    public int $id;

    #[Description('The Joomla content type of this item, always com_content.article.')]
    #[Guarded]
    public string $typeAlias;

    #[Description('The identifier of the ACL asset backing this article.')]
    #[Guarded]
    public int $asset_id;

    #[Required([ResourceProfile::CREATE])]
    public string $title;

    #[Description('The complete article text accepted by the established create endpoint.')]
    public string $text;

    #[Description('Tag identifiers when writing. Reading returns the full tag objects.')]
    #[Items('integer', [ResourceProfile::CREATE, ResourceProfile::UPDATE])]
    #[Items('object', [ResourceProfile::READ, ResourceProfile::LIST])]
    public array $tags = [];

    #[Description('The language code. Use * for all languages.')]
    #[Example('*')]
    public string $language = '*';

    #[Description('The publication state: 1 published, 0 unpublished, 2 archived or -2 trashed.')]
    #[Example(1)]
    #[Optional([ResourceProfile::CREATE])]
    public ArticleState $state;

    #[Description('The category identifier for list and read endpoints.')]
    #[Hidden([ResourceProfile::LIST, ResourceProfile::READ])]
    public int $catid;

    # Folks, please, don't judge us: this has been legacy behavior of the webservices API
    #[Description('The category identifier for create and update endpoints.')]
    #[Guarded]
    #[Hidden([ResourceProfile::CREATE, ResourceProfile::UPDATE])]
    public object $category;

    #[Optional([ResourceProfile::CREATE])]
    public ArticleImage $images;

    #[Description('The meta keywords, as a comma separated list.')]
    public string $metakey = '';

    #[Description('The meta description conveyed in the description meta tag.')]
    public string $metadesc = '';

    #[Optional([ResourceProfile::CREATE])]
    public ArticleMetadata $metadata;

    #[Description('The identifier of the view access level that is allowed to see this article.')]
    public int $access = 1;

    #[Description('Whether the article is featured: 1 featured, 0 not featured.')]
    public int $featured = 0;

    #[Description('The alias used as part of the URL. Left empty, Joomla derives it from the title.')]
    public string $alias = '';

    #[Description('An internal note shown only in the administrator interface.')]
    public ?string $note = null;

    #[Description('When the article starts being published. Null publishes it immediately.')]
    public ?\DateTimeImmutable $publish_up = null;

    #[Description('When the article stops being published. Null never expires it.')]
    public ?\DateTimeImmutable $publish_down = null;

    #[Guarded]
    public \DateTimeImmutable $created;

    #[Description('The creating user identifier. A value of 0 uses the current user.')]
    public int $created_by = 0;

    #[Description('A display name shown instead of the author name, without changing who owns the article.')]
    public string $created_by_alias = '';

    #[Guarded]
    public \DateTimeImmutable $modified;

    #[Guarded]
    #[Hidden([ResourceProfile::LIST])]
    public int $modified_by;

    #[Guarded]
    public int $hits;

    #[Guarded]
    public int $version;

    #[Description('When the article starts being featured. Only meaningful while featured is 1.')]
    public ?\DateTimeImmutable $featured_up = null;

    #[Description('When the article stops being featured. Only meaningful while featured is 1.')]
    public ?\DateTimeImmutable $featured_down = null;

    #[Optional([ResourceProfile::CREATE])]
    public ArticleUrls $urls;

    #[Guarded]
    public ?array $schemaorg = null;
}
