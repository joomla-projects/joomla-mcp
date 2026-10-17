<?php

/**
 * @package     Joomla.API
 * @subpackage  com_fields
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Fields\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class FieldGroup extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        #[Guarded]
        public int $asset_id,
        #[Description("Context where group is used (e.g., com_content.article)")]
        public string $context,
        public string $title,
        public string $note,
        public string $description,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $state,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $ordering,
        public string $params,
        #[Description("use * for all languages")]
        public string $language,
        public string $created,
        public int $created_by,
        #[Guarded]
        public string $modified,
        #[Guarded]
        public int $modified_by,
        public int $access,
        public string $type,
    ) {
    }
}
