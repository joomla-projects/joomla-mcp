<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

/**
 * Transport-specific input produced from one canonical operation argument object.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class OperationInput
{
    /**
     * @param array<string, mixed> $path
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public array $path,
        public array $query,
        public array $body,
    ) {
    }
}
