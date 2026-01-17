<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2ß26 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

interface ResourceInterface
{
    public static function from(mixed $from): ResourceInterface;

    public function toArray(): array;
}
