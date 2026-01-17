<?php

/**
 * @package     Joomla.API
 * @subpackage  com_categories
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Categories\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Category extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $title,
        public string $alias,
        public string $note,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $published,
        public int $access,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $created_user_id,
        public int $parent_id,
        #[Guarded]
        public int $level,
        public string $extension,
        #[Guarded]
        public int $lft,
        #[Guarded]
        public int $rgt,
        #[Description("use * for all languages")]
        public string $language,
        #[Guarded]
        public string $language_title,
        #[Guarded]
        public string $language_image,
        #[Guarded]
        public string $editor,
        #[Guarded]
        public string $access_level,
        #[Guarded]
        public string $author_name,
        #[Guarded]
        public int $count_trashed,
        #[Guarded]
        public int $count_unpublished,
        #[Guarded]
        public int $count_published,
        #[Guarded]
        public int $count_archived,
        public string $params,
        public string $description,
    )
    {
    }
}
