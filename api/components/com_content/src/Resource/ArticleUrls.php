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
 * Represents an article's `urls` field (the three link slots a, b and c).
 *
 * Keys match the raw registry stored in the `#__content.urls` column. Every slot is empty by default, so a client
 * may supply as few of them as it likes.
 *
 * @since  __DEPLOY_VERSION__
 */
class ArticleUrls
{
    #[Description('The link address of slot A.')]
    #[Example('https://example.org')]
    public string $urla = '';

    #[Description('The text shown for the link in slot A.')]
    public string $urlatext = '';

    #[Description('How the link in slot A opens: 0 in the parent window, 1 in a new window, 2 in a popup, 3 in a modal. An empty value uses the global setting.')]
    #[Example('0')]
    public string $targeta = '';

    #[Description('The link address of slot B.')]
    public string $urlb = '';

    #[Description('The text shown for the link in slot B.')]
    public string $urlbtext = '';

    #[Description('How the link in slot B opens. Accepts the same values as targeta.')]
    public string $targetb = '';

    #[Description('The link address of slot C.')]
    public string $urlc = '';

    #[Description('The text shown for the link in slot C.')]
    public string $urlctext = '';

    #[Description('How the link in slot C opens. Accepts the same values as targeta.')]
    public string $targetc = '';
}
