<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource\Attribute;

/**
 * Overrides whether a resource schema accepts properties which are not declared by the DTO.
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AdditionalProperties
{
    public function __construct(public bool $allowed = true)
    {
    }
}
