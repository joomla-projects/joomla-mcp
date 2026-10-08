<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Example;

/**
 * Represents an article's `metadata` field.
 *
 * Keys match the raw registry stored in the `#__content.metadata` column.
 *
 * @since  __DEPLOY_VERSION__
 */
class ArticleMetadata
{
    #[Description('The robots meta tag. Accepts: index, follow / noindex, follow / index, nofollow / noindex, nofollow. An empty value uses the global setting.')]
    #[Example('index, follow')]
    public string $robots = '';

    #[Description('The content author conveyed in the author meta tag.')]
    public string $author = '';

    #[Description('The rights others have to use this content, conveyed in the rights meta tag.')]
    public string $rights = '';
}
