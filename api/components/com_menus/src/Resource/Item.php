<?php

/**
 * @package     Joomla.API
 * @subpackage  com_menus
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Item extends Resource
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
        public string $menutype,
        #[Guarded]
        public string $path,
        #[Guarded]
        public string $link,
        public string $type,
        #[Guarded]
        public int $lft,
        #[Guarded]
        public int $rgt,
        #[Description("use 1 for published, 0 for unpublished, -2 for trashed")]
        public int $published,
        public int $component_id,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $browserNav,
        public int $access,
        public string $img,
        public int $template_style_id,
        public string $params,
        public int $home,
        #[Description("use * for all languages")]
        public string $language,
        public int $client_id,
        public string $publish_up,
        public string $publish_down,
        public string $request,
        public string $associations,
        #[Guarded]
        public int $menuordering,
    )
    {
    }
}
