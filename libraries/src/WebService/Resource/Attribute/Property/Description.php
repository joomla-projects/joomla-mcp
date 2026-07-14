<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource\Attribute\Property;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Description
{
    public function __construct(
        string $description
    ) {

    }
}
