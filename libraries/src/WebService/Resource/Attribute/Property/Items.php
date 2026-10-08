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
 * Describes the item type of an array property for one or more resource profiles.
 *
 * The type may be a JSON Schema primitive, a PHP class name or "object".
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class Items
{
    /**
     * @param string       $type The item type.
     * @param list<string> $on   Profiles for which this item type applies. An empty list applies to every profile.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public string $type, public array $on = [])
    {
    }

    public function appliesTo(string $profile): bool
    {
        return $this->on === [] || \in_array($profile, $this->on, true);
    }
}
