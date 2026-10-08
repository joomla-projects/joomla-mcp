<?php

/**
 * @package     Joomla.API
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Privacy\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Consent extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public int $user_id,
        #[Description("use 1 for accepted, 0 for pending")]
        public int $state,
        #[Guarded]
        public string $created,
        public string $subject,
        public string $body,
        public int $remind,
        #[Guarded]
        public string $token,
        #[Guarded]
        public string $username,
    ) {
    }
}
