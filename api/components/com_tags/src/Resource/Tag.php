<?php

/**
 * @package     Joomla.API
 * @subpackage  com_tags
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Tags\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Tag extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $title,
        public string $alias,
        public string $note,
        public int $parent_id,
        #[Guarded]
        public int $level,
        public string $typeAlias,
        #[Guarded]
        public string $path,
        #[Guarded]
        public int $lft,
        #[Guarded]
        public int $rgt,
        public string $description,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $published,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $access,
        public string $params,
        public string $metadesc,
        public string $metakey,
        public string $metadata,
        public int $created_user_id,
        public string $created_time,
        public string $created_by_alias,
        #[Guarded]
        public int $modified_user_id,
        #[Guarded]
        public string $modified_time,
        public string $images,
        public string $urls,
        #[Guarded]
        public int $hits,
        #[Description("use * for all languages")]
        public string $language,
        #[Guarded]
        public int $version,
        public string $publish_up,
        public string $publish_down,
    )
    {
    }
}
