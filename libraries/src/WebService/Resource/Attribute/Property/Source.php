<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource\Attribute\Property;

use Joomla\CMS\WebService\Resource\ResourceProfile;

/**
 * Overrides the transport name of a resource property for selected profiles.
 *
 * The PHP property name remains the canonical name used by application code and MCP tools. The source name is used by
 * transport projections, for example when an established REST endpoint expects catid rather than category.
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class Source
{
    /**
     * @param list<string> $on
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public string $name,
        public array $on = [ResourceProfile::CREATE, ResourceProfile::UPDATE],
    ) {
    }

    public function appliesTo(string $profile): bool
    {
        return \in_array($profile, $this->on, true);
    }
}
