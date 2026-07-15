<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;
use Joomla\Component\Content\Api\Resource\Enum\ArticleState;

class Article extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        #[Guarded]
        public int $asset_id,
        public string $title,
        public string $text,
        public array $tags,
        #[Description("use * for all languages")]
        public string $language,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public ArticleState $state,
        public int $category,
        public ArticleImage $images,
        public string $metakey,
        public string $metadesc,
        public ArticleMetadata $metadata,
        public int $access,
        public int $featured,
        public string $alias,
        public string $note,
        public ?string $publish_up,
        public ?string $publish_down,
        public string $created,
        public int $created_by,
        public string $created_by_alias,
        #[Guarded]
        public string $modified,
        #[Guarded]
        public int $modified_by,
        #[Guarded]
        public int $hits,
        #[Guarded]
        public int $version,
        public ?string $featured_up,
        public ?string $featured_down,
        public ArticleUrls $urls,
        public ?array $schemaorg,
    ) {
    }
}
