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
use Joomla\CMS\WebService\Resource\Attribute\Property\Example;
use Joomla\Component\Content\Api\Resource\Enum\ArticleState;

/**
 * Typed query contract for the article collection.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ArticleListQuery
{
    public ?int $author         = null;
    public ?int $category       = null;
    public ?ArticleState $state = null;
    public ?int $featured       = null;
    public ?int $tag            = null;
    public ?string $language    = null;
    public ?string $search      = null;

    #[Description('Only articles modified at or after this moment.')]
    public ?\DateTimeImmutable $modified_start = null;

    #[Description('Only articles modified at or before this moment.')]
    public ?\DateTimeImmutable $modified_end = null;

    #[Description('The check-out state: -1 only checked out articles, 0 only articles that are not checked out, or a user identifier to select the articles checked out by that user.')]
    #[Example(0)]
    public ?int $checked_out = null;

    #[Description('The workflow stage identifier. Ignored unless the "Enable Workflow" option is turned on for com_content.')]
    public ?int $stage = null;

    #[Description('The model field used to order the result set.')]
    public string $ordering = 'id';

    #[Description('The ordering direction. Accepts: asc, desc.')]
    public string $direction = 'asc';
}
