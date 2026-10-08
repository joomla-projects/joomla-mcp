<?php

/**
 * @package     Joomla.API
 * @subpackage  com_config
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Config\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class ConfigItem extends Resource
{
    public function __construct(
        #[Guarded]
        #[Description("Extension ID of the configuration owner")]
        public int $id,
        #[Description("Configuration key name")]
        public string $key,
        #[Description("Configuration value (can be string, int, bool, or array)")]
        public mixed $value,
    ) {
    }
}
