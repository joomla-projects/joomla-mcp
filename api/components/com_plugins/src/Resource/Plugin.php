<?php

/**
 * @package     Joomla.API
 * @subpackage  com_plugins
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Plugins\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Plugin extends Resource
{
    public function __construct(
        #[Guarded]
        #[Description("Plugin ID (mapped from extension_id)")]
        public int $id,
        public string $name,
        #[Description("Plugin type (e.g., system, content, user, authentication)")]
        public string $type,
        #[Description("Plugin element/filename")]
        #[Guarded]
        public string $element,
        public string $changelogurl,
        #[Description("Plugin folder/group")]
        #[Guarded]
        public string $folder,
        #[Description("use 0 for site, 1 for administrator")]
        public int $client_id,
        #[Description("use 1 for enabled, 0 for disabled")]
        public int $enabled,
        public int $access,
        #[Description("use 1 if plugin is protected (core), 0 for user-installed")]
        #[Guarded]
        public int $protected,
        #[Guarded]
        public int $checked_out,
        #[Guarded]
        public string $checked_out_time,
        public int $ordering,
        #[Description("use 1 for enabled, 0 for disabled")]
        public int $state,
    )
    {
    }
}
