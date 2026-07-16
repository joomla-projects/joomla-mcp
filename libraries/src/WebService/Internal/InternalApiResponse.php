<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Internal;

/**
 * Normalised response returned by an internal API dispatcher.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class InternalApiResponse
{
    /**
     * @param array<string, list<string>> $headers
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public int $statusCode,
        public mixed $body = null,
        public array $headers = [],
        public string $mediaType = 'application/vnd.api+json',
    ) {
    }
}
