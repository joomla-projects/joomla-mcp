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
 * Contract for typed web service resources.
 *
 * @since  __DEPLOY_VERSION__
 */
interface ResourceInterface
{
    public static function from(mixed $from): ResourceInterface;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
