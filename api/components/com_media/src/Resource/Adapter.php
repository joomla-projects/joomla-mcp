<?php

/**
 * @package     Joomla.API
 * @subpackage  com_media
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Media\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Resource;

class Adapter extends Resource
{
    public function __construct(
        #[Description("Unique provider/adapter ID")]
        public string $provider_id,
        #[Description("Display name of the media adapter")]
        public string $name,
        #[Description("Root path for this adapter")]
        public string $path,
    ) {
    }
}
