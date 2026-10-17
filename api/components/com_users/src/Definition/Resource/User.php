<?php

/**
 * @package     Joomla.API
 * @subpackage  com_users
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class User extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $name,
        public string $username,
        public string $email,
        public string $groups,
        #[Guarded]
        public string $registerDate,
        #[Guarded]
        public string $lastvisitDate,
        #[Guarded]
        public string $lastResetTime,
        #[Guarded]
        public int $resetCount,
        #[Description("use 1 to send email, 0 to not send email")]
        public int $sendEmail,
        #[Description("use 0 for unblocked, 1 for blocked")]
        public int $block,
    ) {
    }
}
