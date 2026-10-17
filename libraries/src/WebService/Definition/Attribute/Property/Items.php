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
 * The type may be a JSON Schema primitive, a PHP class name or "object".
 *
 * @since  __DEPLOY_VERSION__
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final readonly class Items
{
    /**
     * @param string       $type The item type.
     * @param list<string> $on   Operation Types for which this item type applies. An empty list applies to every type.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public string $type, public array $on = [])
    {
    }

    public function appliesTo(OperationType $operationType): bool
    {
        return $this->on === [] || \in_array($operationType, $this->on, true);
    }
}
