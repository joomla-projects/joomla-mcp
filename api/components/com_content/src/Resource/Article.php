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

    #[Guarded]
    public string $typeAlias;

    #[Guarded]
    public int $asset_id;

    #[Required([ResourceProfile::CREATE])]
    public string $title;

    #[Description('The complete article text accepted by the established create endpoint.')]
    public string $text;

    #[Items('integer', [ResourceProfile::CREATE, ResourceProfile::UPDATE])]
    #[Items('object', [ResourceProfile::READ, ResourceProfile::LIST])]
    public array $tags = [];

    #[Description('The language code. Use * for all languages.')]
    #[Example('*')]
    public string $language = '*';

    #[Description('The publication state: 1 published, 0 unpublished, 2 archived or -2 trashed.')]
    #[Example(1)]
    public ArticleState $state;

    #[Description('The category identifier for list and read endpoints.')]
    #[Hidden([ResourceProfile::LIST, ResourceProfile::READ])]
    public int $catid;

    #[Description('The category identifier for create and update endpoints.')]
    #[Guarded]
    #[Hidden([ResourceProfile::CREATE, ResourceProfile::UPDATE])]
    public object $category;

    public ArticleImage $images;
    public string $metakey  = '';
    public string $metadesc = '';
    public ArticleMetadata $metadata;
    public int $access                       = 1;
    public int $featured                     = 0;
    public string $alias                     = '';
    public ?string $note                     = null;
    public ?\DateTimeImmutable $publish_up   = null;
    public ?\DateTimeImmutable $publish_down = null;

    #[Guarded]
    public \DateTimeImmutable $created;

    #[Description('The creating user identifier. A value of 0 uses the current user.')]
    public int $created_by          = 0;
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

    public ?\DateTimeImmutable $featured_up   = null;
    public ?\DateTimeImmutable $featured_down = null;
    public ArticleUrls $urls;

    #[Guarded]
    public ?array $schemaorg = null;
}
