<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Resource;

/**
 * Represents an article's `images` field (the intro and fulltext image set).
 *
 * Keys match the raw registry stored in the `#__content.images` column.
 */
class ArticleImage
{
    public function __construct(
        public string $image_intro,
        public string $image_intro_alt,
        public bool $image_intro_alt_empty,
        public string $imgclass_intro,
        public string $float_intro,
        public string $image_intro_caption,
        public string $image_fulltext,
        public string $image_fulltext_alt,
        public bool $image_fulltext_alt_empty,
        public string $imgclass_fulltext,
        public string $float_fulltext,
        public string $image_fulltext_caption,
    ) {
    }
}
