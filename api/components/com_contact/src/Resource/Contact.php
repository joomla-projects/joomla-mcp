<?php

/**
 * @package     Joomla.API
 * @subpackage  com_contact
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Contact\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Contact extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $name,
        public string $alias,
        public int $category,
        public string $created,
        public int $created_by,
        public string $created_by_alias,
        #[Guarded]
        public string $modified,
        #[Guarded]
        public int $modified_by,
        public string $image,
        public string $tags,
        public int $featured,
        public string $publish_up,
        public string $publish_down,
        #[Guarded]
        public int $version,
        #[Guarded]
        public int $hits,
        public string $metakey,
        public string $metadesc,
        public string $metadata,
        public string $con_position,
        public string $address,
        public string $suburb,
        public int $state,
        public string $country,
        public string $postcode,
        public string $telephone,
        public string $fax,
        public string $misc,
        public string $email_to,
        public int $default_con,
        public int $user_id,
        public int $access,
        public string $mobile,
        public string $webpage,
        public string $sortname1,
        public string $sortname2,
        public string $sortname3,
    )
    {
    }
}
