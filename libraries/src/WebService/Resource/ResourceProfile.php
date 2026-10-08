<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

/**
 * Standard resource schema profiles.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceProfile
{
    public const READ   = 'read';
    public const LIST   = 'list';
    public const CREATE = 'create';
    public const UPDATE = 'update';

    private function __construct()
    {
    }
}
