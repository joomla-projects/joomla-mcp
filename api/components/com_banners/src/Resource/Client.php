<?php

/**
 * @package     Joomla.API
 * @subpackage  com_banners
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Banners\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Client extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $typeAlias,
        public string $name,
        public string $contact,
        public string $email,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public string $extrainfo,
        #[Description("use 1 for published, 0 for unpublished, 2 for archived, -2 for trashed")]
        public int $state,
        public string $metakey,
        public int $own_prefix,
        public string $metakey_prefix,
        #[Description("use -1 for global, 1 for unlimited, 2 for yearly, 3 for monthly, 4 for weekly, 5 for daily")]
        public int $purchase_type,
        #[Description("use -1 for global, 0 for no, 1 for yes")]
        public int $track_clicks,
        #[Description("use -1 for global, 0 for no, 1 for yes")]
        public int $track_impressions,
    )
    {
    }
}
