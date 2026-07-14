<?php

/**
 * @package     Joomla.API
 * @subpackage  com_modules
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Modules\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Module extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        #[Guarded]
        public int $asset_id,
        public string $title,
        public string $note,
        public string $content,
        public int $ordering,
        #[Description("Module position (e.g., sidebar-right, footer)")]
        public string $position,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public string $publish_up,
        public string $publish_down,
        #[Description("use 1 for published, 0 for unpublished, -2 for trashed")]
        public int $published,
        #[Description("Module type (e.g., mod_custom, mod_menu)")]
        public string $module,
        public int $access,
        #[Description("use 1 to show title, 0 to hide title")]
        public int $showtitle,
        public string $params,
        #[Description("use 0 for site, 1 for administrator")]
        public int $client_id,
        #[Description("use * for all languages")]
        public string $language,
        public string $assigned,
        public string $assignment,
        public string $xml,
    ) {
    }
}
