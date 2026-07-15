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
 * Represents an article's `urls` field (the three link slots a, b and c).
 *
 * Keys match the raw registry stored in the `#__content.urls` column.
 */
class ArticleUrls
{
    public function __construct(
        public string $urla,
        public string $urlatext,
        public string $targeta,
        public string $urlb,
        public string $urlbtext,
        public string $targetb,
        public string $urlc,
        public string $urlctext,
        public string $targetc,
    ) {
    }
}
