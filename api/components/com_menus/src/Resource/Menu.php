<?php

/**
 * @package     Joomla.API
 * @subpackage  com_menus
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Menu extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $menutype,
        public string $title,
        public string $description,
        public int $client_id,
        #[Guarded]
        public int $count_published,
        #[Guarded]
        public int $count_unpublished,
        #[Guarded]
        public int $count_trashed,
    ) {
    }
}
