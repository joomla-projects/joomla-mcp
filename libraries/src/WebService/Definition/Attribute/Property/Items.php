<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Definition\Attribute\Property;

use Joomla\CMS\WebService\Operation\OperationType;

/**
 * Describes the item type of an array property for one or more operation types.
 *
 * The type may be a JSON Schema primitive, a PHP class name or "object". A keyed array is a map whose keys carry
 * meaning, such as tag identifiers. It is encoded as a JSON object, and the type describes its values.
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class Items
{
    /**
     * @param string       $type  The item type.
     * @param list<string> $on    Operation Types for which this item type applies. An empty list applies to every type.
     * @param bool         $keyed Whether the array is keyed, and therefore a JSON object rather than a list.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public string $type, public array $on = [], public bool $keyed = false)
    {
    }

    public function appliesTo(OperationType $operationType): bool
    {
        return $this->on === [] || \in_array($operationType, $this->on, true);
    }
}
