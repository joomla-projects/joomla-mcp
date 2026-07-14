<?php

/**
 * @package     Joomla.API
 * @subpackage  com_languages
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Languages\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Language extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        #[Guarded]
        public int $asset_id,
        #[Description("Language tag (e.g., en-GB, de-DE)")]
        public string $lang_code,
        public string $title,
        public string $title_native,
        #[Description("SEF language code (e.g., en, de)")]
        public string $sef,
        public string $image,
        public string $description,
        public string $metakey,
        public string $metadesc,
        public string $sitename,
        #[Description("use 1 for published, 0 for unpublished, -2 for trashed")]
        public int $published,
        public int $access,
        public int $ordering,
        #[Guarded]
        public string $access_level,
        #[Description("use 1 if default language, 0 otherwise")]
        public int $home,
    ) {
    }
}
