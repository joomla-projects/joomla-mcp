<?php

/**
 * @package     Joomla.API
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Privacy\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class Request extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        public string $email,
        #[Guarded]
        public string $requested_at,
        #[Description("use 0 for pending, -1 for invalid, 1 for confirmed, 2 for completed")]
        #[Guarded]
        public int $status,
        #[Description("Request type: export or remove")]
        public string $request_type,
    ) {
    }
}
