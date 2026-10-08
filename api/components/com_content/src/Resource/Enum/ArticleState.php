<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Resource\Enum;

enum ArticleState: int
{
    case Published   = 1;
    case Unpublished = 0;
    case Trashed     = -2;
    case Archived    = 2;
}
