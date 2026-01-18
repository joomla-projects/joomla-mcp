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
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class MediaItem extends Resource
{
    public function __construct(
        #[Guarded]
        #[Description("Media items have no database ID, always 0")]
        public string $id,
        #[Description("use 'file' or 'dir' for file/directory type")]
        public string $type,
        public string $name,
        #[Description("Full path to the media item")]
        public string $path,
        #[Description("File extension (e.g., jpg, png, pdf)")]
        public string $extension,
        #[Description("File size in bytes")]
        public int $size,
        #[Description("MIME type (e.g., image/jpeg, application/pdf)")]
        public string $mime_type,
        #[Description("Image width in pixels (for images only)")]
        public int $width,
        #[Description("Image height in pixels (for images only)")]
        public int $height,
        #[Guarded]
        public string $create_date,
        #[Guarded]
        public string $create_date_formatted,
        #[Guarded]
        public string $modified_date,
        #[Guarded]
        public string $modified_date_formatted,
        #[Description("Path to thumbnail image")]
        public string $thumb_path,
        #[Description("Media adapter ID used for this item")]
        public string $adapter,
        #[Description("File content (for text files)")]
        public string $content,
        #[Description("Public URL to access the media")]
        public string $url,
        #[Description("Temporary URL for restricted access")]
        public string $tempUrl,
    )
    {
    }
}
