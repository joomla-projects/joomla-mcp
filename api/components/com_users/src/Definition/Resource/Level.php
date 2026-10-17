<?php

/**
 * @package     Joomla.API
 * @subpackage  com_users
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class Level extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $title,
        public string $rules,
    ) {
    }
}
