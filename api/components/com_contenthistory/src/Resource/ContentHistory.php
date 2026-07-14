<?php

/**
 * @package     Joomla.API
 * @subpackage  com_contenthistory
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Contenthistory\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class ContentHistory extends Resource
{
    public function __construct(
        #[Guarded]
        #[Description("Version ID (mapped from version_id)")]
        public int $id,
        #[Description("ID of the content item being versioned")]
        public int $ucm_item_id,
        #[Description("Content type ID")]
        public int $ucm_type_id,
        public string $version_note,
        #[Guarded]
        public string $save_date,
        #[Description("User ID of the editor who created this version")]
        public int $editor_user_id,
        #[Guarded]
        public int $character_count,
        #[Guarded]
        #[Description("SHA1 hash of version data for comparison")]
        public string $sha1_hash,
        #[Description("JSON data containing the versioned content")]
        public string $version_data,
        #[Description("use 1 to keep version forever, 0 for normal retention")]
        public int $keep_forever,
        #[Guarded]
        public string $editor,
    )
    {
    }
}
