<?php

/**
 * @package     Joomla.API
 * @subpackage  com_redirect
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Redirect\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Redirect extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        public string $old_url,
        public string $new_url,
        #[Guarded]
        public string $referer,
        public string $comment,
        #[Guarded]
        public int $hits,
        #[Description("use 1 for enabled, 0 for disabled, 2 for archived, -2 for trashed")]
        public int $published,
        #[Guarded]
        public string $created_date,
        #[Guarded]
        public string $modified_date,
        #[Description("HTTP status code for redirect (301, 302, etc.)")]
        public int $header,
    ) {
    }
}
