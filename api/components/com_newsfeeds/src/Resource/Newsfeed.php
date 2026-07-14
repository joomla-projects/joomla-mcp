<?php

/**
 * @package     Joomla.API
 * @subpackage  com_newsfeeds
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Newsfeeds\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Newsfeed extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $name,
        public string $alias,
        public int $category,
        public string $link,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $published,
        public int $numarticles,
        public int $cache_time,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $ordering,
        public int $rtl,
        public int $access,
        #[Description("use * for all languages")]
        public string $language,
        public string $params,
        public string $created,
        public int $created_by,
        public string $created_by_alias,
        #[Guarded]
        public string $modified,
        #[Guarded]
        public int $modified_by,
        public string $metakey,
        public string $metadesc,
        public string $metadata,
        public string $publish_up,
        public string $publish_down,
        public string $description,
        #[Guarded]
        public int $version,
        #[Guarded]
        public int $hits,
        public string $images,
        public string $tags,
    ) {
    }
}
