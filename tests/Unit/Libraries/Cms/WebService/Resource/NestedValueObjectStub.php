<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Resource;

/**
 * A nested contract class that is not a Resource, used to pin the profile convention for plain value objects.
 *
 * @since  __DEPLOY_VERSION__
 */
class NestedValueObjectStub
{
    public string $mandatory;

    public string $optional = '';
}
