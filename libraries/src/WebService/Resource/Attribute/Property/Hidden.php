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
 * Removes a property from selected resource projections.
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Hidden
{
    /**
     * @param list<string> $on
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public array $on)
    {
    }

    public function appliesTo(string $profile): bool
    {
        return \in_array($profile, $this->on, true);
    }
}
