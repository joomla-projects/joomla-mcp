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
use Joomla\CMS\WebService\Definition\Attribute\Resource\AdditionalProperties;
use Joomla\CMS\WebService\Operation\OperationType;

/**
 * Hydrates typed resources whilst preserving operation-specific presence semantics.
 *
 * Create hydration keeps declared defaults. Update hydration unsets every declared property first, so an omitted
 * property remains uninitialised whilst an explicit null value remains distinguishable.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceHydrator
{
    /**
     * @param class-string<ResourceInterface> $resourceClass
     * @param array<string, mixed>             $data
     *
     * @since  __DEPLOY_VERSION__
     */
    public function hydrate(
        string $resourceClass,
        array $data,
        OperationType $operationType = OperationType::READ,
    ): ResourceInterface {
        $reflection = new \ReflectionClass($resourceClass);

        if (!$reflection->implementsInterface(ResourceInterface::class)) {
            throw new \InvalidArgumentException(\sprintf('%s is not a web service resource.', $resourceClass));
        }

        /** @var ResourceInterface $resource */
        $resource = $reflection->newInstanceWithoutConstructor();

        if ($operationType === OperationType::UPDATE) {
            $this->unsetDeclaredDefaults($reflection, $resource);
        }

        $additionalProperties       = $reflection->getAttributes(AdditionalProperties::class);
        $allowsAdditionalProperties = $additionalProperties !== []
            && $additionalProperties[0]->newInstance()->allowed;

        foreach ($data as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                if ($allowsAdditionalProperties && $resource instanceof BaseResource) {
                    $resource->setAdditionalProperty($name, $value);
                    continue;
                }

                throw new \InvalidArgumentException(
                    \sprintf('Property %s is not declared by %s.', $name, $resourceClass),
                );
            }

            $property = $reflection->getProperty($name);

            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $this->assertPropertyAllowed($property, $operationType);
            $property->setValue($resource, $this->hydrateValue($property->getType(), $value, $operationType));
        }

        return $resource;
    }

    private function unsetDeclaredDefaults(\ReflectionClass $reflection, ResourceInterface $resource): void
    {
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic() && $property->isInitialized($resource)) {
                unset($resource->{$property->getName()});
            }
        }
    }

    private function assertPropertyAllowed(\ReflectionProperty $property, OperationType $operationType): void
    {
        $hiddenAttributes = $property->getAttributes(Hidden::class);

        if ($hiddenAttributes !== [] && $hiddenAttributes[0]->newInstance()->appliesTo($operationType)) {
            throw new \InvalidArgumentException(
                \sprintf('Property %s is not available in the %s operation.', $property->getName(), $operationType->name),
            );
        }

        if (
            \in_array($operationType, [OperationType::CREATE, OperationType::UPDATE], true)
            && $property->getAttributes(Guarded::class) !== []
            && $property->getAttributes(Guarded::class)[0]->newInstance()->appliesTo($operationType)
        ) {
            throw new \InvalidArgumentException(
                \sprintf('Property %s is guarded and cannot be written.', $property->getName()),
            );
        }
    }

    private function hydrateValue(?\ReflectionType $type, mixed $value, OperationType $operationType): mixed
    {
        if ($value === null || !$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $typeName = $type->getName();

        if (is_a($typeName, \DateTimeInterface::class, true) && \is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        if (is_a($typeName, \BackedEnum::class, true)) {
            return $typeName::from($value);
        }

        if (is_a($typeName, ResourceInterface::class, true) && \is_array($value)) {
            return $this->hydrate($typeName, $value, $operationType);
        }

        return $value;
    }
}
