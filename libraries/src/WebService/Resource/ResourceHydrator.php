<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

use Joomla\CMS\WebService\Resource\Attribute\AdditionalProperties;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Resource\Attribute\Property\WriteOnly;

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
        string $profile = ResourceProfile::READ,
    ): ResourceInterface {
        $reflection = new \ReflectionClass($resourceClass);

        if (!$reflection->implementsInterface(ResourceInterface::class)) {
            throw new \InvalidArgumentException(\sprintf('%s is not a web service resource.', $resourceClass));
        }

        /** @var ResourceInterface $resource */
        $resource = $reflection->newInstanceWithoutConstructor();

        if ($profile === ResourceProfile::UPDATE) {
            $this->unsetDeclaredDefaults($reflection, $resource);
        }

        $additionalProperties       = $reflection->getAttributes(AdditionalProperties::class);
        $allowsAdditionalProperties = $additionalProperties !== []
            && $additionalProperties[0]->newInstance()->allowed;

        foreach ($data as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                if ($allowsAdditionalProperties && $resource instanceof Resource) {
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

            $this->assertPropertyAllowed($property, $profile);
            $property->setValue($resource, $this->hydrateValue($property->getType(), $value, $profile));
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

    private function assertPropertyAllowed(\ReflectionProperty $property, string $profile): void
    {
        $hiddenAttributes = $property->getAttributes(Hidden::class);

        if ($hiddenAttributes !== [] && $hiddenAttributes[0]->newInstance()->appliesTo($profile)) {
            throw new \InvalidArgumentException(
                \sprintf('Property %s is not available in the %s profile.', $property->getName(), $profile),
            );
        }

        if (
            \in_array($profile, [ResourceProfile::CREATE, ResourceProfile::UPDATE], true)
            && $property->getAttributes(Guarded::class) !== []
        ) {
            throw new \InvalidArgumentException(
                \sprintf('Property %s is guarded and cannot be written.', $property->getName()),
            );
        }

        if (
            \in_array($profile, [ResourceProfile::READ, ResourceProfile::LIST], true)
            && $property->getAttributes(WriteOnly::class) !== []
        ) {
            throw new \InvalidArgumentException(
                \sprintf('Property %s is write-only.', $property->getName()),
            );
        }
    }

    private function hydrateValue(?\ReflectionType $type, mixed $value, string $profile): mixed
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
            return $this->hydrate($typeName, $value, $profile);
        }

        return $value;
    }
}
