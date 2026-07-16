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
    #[Description('Only articles created by this user identifier.')]
    public ?int $author = null;

    #[Description('Only articles in this category identifier. The category is addressed as catid on the article itself.')]
    public ?int $category = null;

    #[Description('Only articles in this publication state: 1 published, 0 unpublished, 2 archived or -2 trashed. Omit to use the default, which returns published and unpublished articles.')]
    #[Example(1)]
    public ?ArticleState $state = null;

    #[Description('Only featured articles when 1, only articles that are not featured when 0.')]
    #[Example(1)]
    public ?int $featured = null;

    #[Description('Only articles carrying this tag identifier.')]
    public ?int $tag = null;

    #[Description('Only articles in this language code. Articles assigned to all languages use *.')]
    #[Example('en-GB')]
    public ?string $language = null;

    #[Description(
        'Free text matched against the title and alias. A prefix narrows the search instead: "id:5" matches one '
        . 'identifier, "author:jane" the author name or username, "content:draft" the article text, and '
        . '"checkedout:jane" the name or username of the user holding the check-out.'
    )]
    #[Example('content:release notes')]
    public ?string $search = null;

    #[Description('Only articles modified at or after this moment.')]
    public ?\DateTimeImmutable $modified_start = null;

    #[Description('Only articles modified at or before this moment.')]
    public ?\DateTimeImmutable $modified_end = null;

    #[Description('The check-out state: -1 only checked out articles, 0 only articles that are not checked out, or a user identifier to select the articles checked out by that user.')]
    #[Example(0)]
    public ?int $checked_out = null;

    #[Description('The workflow stage identifier. Ignored unless the "Enable Workflow" option is turned on for com_content.')]
    public ?int $stage = null;

    #[Description(
        'The field the result set is ordered by. Accepts: id, title, alias, catid, category_title, state, access, '
        . 'access_level, created, created_by, created_by_alias, modified, ordering, featured, featured_up, '
        . 'featured_down, language, hits, publish_up, publish_down, checked_out, checked_out_time, author_id, '
        . 'category_id, level or tag.'
    )]
    #[Example('created')]
    public string $ordering = 'id';

    #[Description('The ordering direction. Accepts: asc, desc.')]
    public string $direction = 'asc';

    #[Description('The maximum number of articles to return. Omitted, the component default applies.')]
    #[Example(50)]
    public ?int $limit = null;

    #[Description('The number of articles to skip before the first returned one, for paging through the result set.')]
    #[Example(0)]
    public ?int $offset = null;
}
