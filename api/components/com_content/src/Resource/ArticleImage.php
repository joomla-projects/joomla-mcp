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
 * Represents an article's `images` field (the intro and fulltext image set).
 *
 * Keys match the raw registry stored in the `#__content.images` column. Both image slots are empty by default, so a
 * client may supply either, both or neither.
 *
 * @since  __DEPLOY_VERSION__
 */
class ArticleImage
{
    #[Description('The intro image path, relative to the site root.')]
    #[Example('images/joomla_black.png')]
    public string $image_intro = '';

    #[Description('The alternative text of the intro image.')]
    public string $image_intro_alt = '';

    #[Description('Set when the intro image is decorative and therefore needs no alternative text.')]
    public bool $image_intro_alt_empty = false;

    #[Description('A CSS class applied to the intro image. An empty value uses the global setting.')]
    public string $imgclass_intro = '';

    #[Description('A CSS class applied to the intro image figure, for example float-start or float-end. An empty value uses the global setting.')]
    #[Example('float-start')]
    public string $float_intro = '';

    #[Description('The caption of the intro image.')]
    public string $image_intro_caption = '';

    #[Description('The fulltext image path, relative to the site root.')]
    public string $image_fulltext = '';

    #[Description('The alternative text of the fulltext image.')]
    public string $image_fulltext_alt = '';

    #[Description('Set when the fulltext image is decorative and therefore needs no alternative text.')]
    public bool $image_fulltext_alt_empty = false;

    #[Description('A CSS class applied to the fulltext image. An empty value uses the global setting.')]
    public string $imgclass_fulltext = '';

    #[Description('A CSS class applied to the fulltext image figure. Accepts the same values as float_intro.')]
    public string $float_fulltext = '';

    #[Description('The caption of the fulltext image.')]
    public string $image_fulltext_caption = '';
}
