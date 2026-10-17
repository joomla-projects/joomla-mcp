<?php

/**
 * @package     Joomla.API
 * @subpackage  com_messages
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Messages\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class Message extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        #[Guarded]
        public int $user_id_from,
        public int $user_id_to,
        #[Guarded]
        public string $date_time,
        public int $priority,
        public string $subject,
        public string $message,
        #[Description("use 0 for unread, 1 for read, -1 for trashed")]
        public int $state,
        #[Guarded]
        public string $user_from,
    ) {
    }
}
