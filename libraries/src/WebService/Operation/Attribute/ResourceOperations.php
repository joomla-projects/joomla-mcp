<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation\Attribute;

/**
 * Opts a Joomla API controller into convention-derived CRUD operations.
 *
 * Every argument is an override. The normal case is simply #[ResourceOperations].
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class ResourceOperations
{
    /**
     * @param class-string|null $resourceClass
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public ?string $resourceClass = null,
        public ?string $basePath = null,
        public ?string $controller = null,
        public bool $publicGets = false,
        public bool $exposeToMcp = true,
    ) {
    }
}
