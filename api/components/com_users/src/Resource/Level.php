<?php

/**
 * @package     Joomla.API
 * @subpackage  com_users
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Level extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $title,
        public string $rules,
    ) {
    }
}
