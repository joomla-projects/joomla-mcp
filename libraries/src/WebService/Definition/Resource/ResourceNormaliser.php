<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Operation\OperationType;

/**
 * Normalises typed resources to operation-specific serialisable arrays.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceNormaliser
{
    /**
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function normalise(
        ResourceInterface $resource,
        OperationType $operationType = OperationType::READ,
    ): array {
        $data = $resource instanceof BaseResource
            ? $this->normaliseValue($resource->getAdditionalProperties(), $operationType)
            : [];
        $reflection = new \ReflectionObject($resource);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (
                $property->isStatic()
                || !$property->isInitialized($resource)
                || !$this->isVisible($property, $operationType)
            ) {
                continue;
            }

            $data[$property->getName()] = $this->normaliseValue($property->getValue($resource), $operationType);
        }

        return $data;
    }

    private function isVisible(\ReflectionProperty $property, OperationType $operationType): bool
    {
        $hiddenAttributes = $property->getAttributes(Hidden::class);

        if ($hiddenAttributes !== [] && $hiddenAttributes[0]->newInstance()->appliesTo($operationType)) {
            return false;
        }

        return !(
            \in_array($operationType, [OperationType::CREATE, OperationType::UPDATE], true)
            && $property->getAttributes(Guarded::class) !== []
        );
    }

    private function normaliseValue(mixed $value, OperationType $operationType): mixed
    {
        if ($value instanceof ResourceInterface) {
            return $this->normalise($value, $operationType);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_array($value)) {
            return array_map(
                fn (mixed $item): mixed => $this->normaliseValue($item, $operationType),
                $value,
            );
        }

        return $value;
    }
}
