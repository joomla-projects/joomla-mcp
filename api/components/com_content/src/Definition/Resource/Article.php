<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Example;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Definition\Attribute\Property\Items;
use Joomla\CMS\WebService\Definition\Attribute\Property\Optional;
use Joomla\CMS\WebService\Definition\Attribute\Property\Required;
use Joomla\CMS\WebService\Definition\Attribute\Resource\AdditionalProperties;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;
use Joomla\CMS\WebService\Operation\OperationType;
use Joomla\Component\Content\Api\Definition\Resource\Enum\ArticleState;

/**
 * Canonical article resource contract.
 *
 * Properties without defaults are required when an article is created. Guarded properties are returned to clients but
 * cannot be mass assigned. No property is required for an update because PATCH is partial by convention.
 *
 * @since  __DEPLOY_VERSION__
 */
#[AdditionalProperties]
final class Article extends BaseResource
{
    #[Guarded]
    public int $id;

    #[Description('The Joomla content type of this item, always com_content.article.')]
    #[Guarded]
    public string $typeAlias;

    #[Description('The identifier of the ACL asset backing this article.')]
    #[Guarded]
    public int $asset_id;

    #[Required([OperationType::CREATE])]
    public string $title;

    // Joomla stores the body split into introtext and fulltext and accepts it differently per operation. A create takes
    // the complete text as articletext and splits it at the readmore tag. A partial update cannot: the API merges the
    // stored introtext and fulltext into the data of a PATCH, and these override a submitted articletext. An update
    // therefore addresses the two parts directly. Reading returns both parts and the combined text.
    // Joomla lets an article be saved without a body, so these stay optional and default to an empty string.
    #[Description('The complete article text. Add a <hr id="system-readmore"> tag to split the intro and full text.')]
    #[Hidden([OperationType::LIST, OperationType::READ, OperationType::UPDATE])]
    public string $articletext = '';

    #[Description('The intro part of the article text, shown before the readmore link.')]
    #[Hidden([OperationType::CREATE])]
    public string $introtext = '';

    #[Description('The full part of the article text, shown after the readmore link.')]
    #[Hidden([OperationType::CREATE])]
    public string $fulltext = '';

    // The webservices build text from introtext and fulltext and then run the content plugins on it. Joomla 6.2 deprecated
    // it for removal in 8.0, without replacement.
    #[Description('The intro and full text joined by a space, after the content plugins have processed it. Deprecated: it will be removed in Joomla 8.0; use introtext and fulltext instead.')]
    #[Hidden([OperationType::CREATE, OperationType::UPDATE])]
    public string $text;

    // The webservices render the tags as a map of tag identifier to title, the way TagsHelper::getTags() returns them.
    #[Description('Tag identifiers when writing. Reading returns an object that maps each tag identifier to its title, for example {"2": "Blog"}.')]
    #[Items('integer', [OperationType::CREATE, OperationType::UPDATE])]
    #[Items('string', [OperationType::READ, OperationType::LIST], keyed: true)]
    public array $tags = [];

    // The webservices themselves require a language on create: without one, saving fails because the language column has
    // no default. The default here makes it optional for MCP callers only.
    #[Description('The language code. Use * for all languages.')]
    #[Example('*')]
    public string $language = '*';

    // The form default of 1 only applies in the administrator. The webservices leave an omitted state to the column
    // default of the content table.
    #[Description('The publication state: 1 published, 0 unpublished, 2 archived or -2 trashed. Omitted on create, the article is saved unpublished (0) when workflows are disabled.')]
    #[Example(1)]
    #[Optional([OperationType::CREATE])]
    public ArticleState $state;

    #[Description('The category identifier. Reading returns the category as category instead.')]
    #[Hidden([OperationType::LIST, OperationType::READ])]
    public int $catid;

    # Folks, please, don't judge us: this has been legacy behavior of the webservices API
    #[Description('The category, as a resource identifier: {"type": "categories", "id": <category identifier>}. Writing uses catid instead.')]
    #[Guarded]
    #[Hidden([OperationType::CREATE, OperationType::UPDATE])]
    public object $category;

    #[Optional([OperationType::CREATE])]
    public ArticleImage $images;

    #[Description('The meta keywords, as a comma separated list.')]
    public string $metakey = '';

    #[Description('The meta description conveyed in the description meta tag.')]
    public string $metadesc = '';

    #[Optional([OperationType::CREATE])]
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

    // The administrator lets the creation date be set on create and edited afterwards, so it is writable in both;
    // omitted on create, Joomla uses the current time.
    #[Description('The creation date and time. Defaults to the current time when omitted.')]
    #[Optional([OperationType::CREATE])]
    public \DateTimeImmutable $created;

    #[Description('The creating user identifier. A value of 0 uses the current user.')]
    public int $created_by = 0;

    #[Description('A display name shown instead of the author name, without changing who owns the article.')]
    public string $created_by_alias = '';

    #[Guarded]
    public \DateTimeImmutable $modified;

    #[Guarded]
    #[Hidden([OperationType::LIST])]
    public int $modified_by;

    #[Guarded]
    public int $hits;

    #[Guarded]
    public int $version;

    #[Description('When the article starts being featured. Only meaningful while featured is 1.')]
    public ?\DateTimeImmutable $featured_up = null;

    #[Description('When the article stops being featured. Only meaningful while featured is 1.')]
    public ?\DateTimeImmutable $featured_down = null;

    // The list query does not select the urls column, so only a single article reports them.
    #[Optional([OperationType::CREATE])]
    #[Hidden([OperationType::LIST])]
    public ArticleUrls $urls;

    // The webservices accept an explicit position on write but never return it, so it is write-only.
    #[Description('The article position within its category. Omitted, Joomla assigns the next position.')]
    #[Optional([OperationType::CREATE])]
    #[Hidden([OperationType::LIST, OperationType::READ])]
    public int $ordering;

    // Only rendered while the schemaorg system plugin is enabled. It is the schema of the chosen type, an object.
    #[Description('The schema.org data of the article, for example {"@type": "Article", "headline": "..."}. Null when the article has none.')]
    #[Guarded]
    public ?object $schemaorg = null;
}
