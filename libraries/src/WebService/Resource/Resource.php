<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

abstract class Resource
{
    public static function from(mixed $from): ResourceInterface
    {

    }

    public static function fromArray(array $array)
    {

    }

    public static function fromObject(object $object)
    {

    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }
}
