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
 * Represents an article's `metadata` field.
 *
 * Keys match the raw registry stored in the `#__content.metadata` column.
 */
class ArticleMetadata
{
    public function __construct(
        public string $robots,
        public string $author,
        public string $rights,
    ) {
    }
}
