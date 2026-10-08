<?php

/**
 * @package     Joomla.API
 * @subpackage  com_fields
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Fields\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Field extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        #[Guarded]
        public int $asset_id,
        #[Description("Context where field is used (e.g., com_content.article)")]
        public string $context,
        public int $group_id,
        public string $title,
        public string $name,
        public string $label,
        public string $default_value,
        #[Description("Field type (text, textarea, calendar, etc.)")]
        public string $type,
        public string $note,
        public string $description,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $state,
        #[Description("use 1 for required, 0 for optional")]
        public int $required,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $ordering,
        public string $params,
        public string $fieldparams,
        #[Description("use * for all languages")]
        public string $language,
        public string $created_time,
        public int $created_user_id,
        #[Guarded]
        public string $modified_time,
        #[Guarded]
        public int $modified_by,
        public int $access,
        public string $assigned_cat_ids,
    ) {
    }
}
