<?php

/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\Query;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;

/**
 * Typed query contract for the article collection.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ArticleListQuery
{
    public ?int $author = null;
    public ?int $category = null;
    public ?string $search = null;
    public ?int $state = null;
    public ?int $featured = null;
    public ?int $tag = null;
    public ?string $language = null;
    public ?\DateTimeImmutable $modified_start = null;
    public ?\DateTimeImmutable $modified_end = null;
    public ?int $checked_out = null;
    public ?int $stage = null;

    #[Description('The model field used to order the result set.')]
    public string $ordering = 'id';

    #[Description('The ordering direction.')]
    public string $direction = 'asc';
}
