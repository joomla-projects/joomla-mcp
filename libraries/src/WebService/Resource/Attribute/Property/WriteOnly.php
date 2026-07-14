<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource\Attribute\Property;

/**
 * Marks a property as writable but not part of read or list representations.
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class WriteOnly
{
}
