<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource\Schema;

use Joomla\CMS\WebService\Resource\Attribute\AdditionalProperties;
use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Example;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Resource\Attribute\Property\Items;
use Joomla\CMS\WebService\Resource\Attribute\Property\Optional;
use Joomla\CMS\WebService\Resource\Attribute\Property\Required;
use Joomla\CMS\WebService\Resource\Attribute\Property\Source;
use Joomla\CMS\WebService\Resource\Attribute\Property\WriteOnly;
use Joomla\CMS\WebService\Resource\ResourceInterface;
use Joomla\CMS\WebService\Resource\ResourceProfile;

/**
 * Builds JSON Schema documents from typed DTOs and resource conventions.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceSchemaFactory
{
    /**
     * @param class-string $className
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(string $className, string $profile = ResourceProfile::READ): array
    {
        $reflection = new \ReflectionClass($className);
        $isResource = $reflection->implementsInterface(ResourceInterface::class);
        $properties = [];
        $required   = [];
        $defaults   = $reflection->getDefaultProperties();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $guarded   = $this->firstAttribute($property, Guarded::class) !== null;
            $writeOnly = $this->firstAttribute($property, WriteOnly::class) !== null;
            $hidden    = $this->firstAttribute($property, Hidden::class);

            if ($hidden instanceof Hidden && $hidden->appliesTo($profile)) {
                continue;
            }

            if (
                $isResource
                && $guarded
                && \in_array($profile, [ResourceProfile::CREATE, ResourceProfile::UPDATE], true)
            ) {
                continue;
            }

            if (
                $isResource
                && $writeOnly
                && \in_array($profile, [ResourceProfile::READ, ResourceProfile::LIST], true)
            ) {
                continue;
            }

            $schema      = $this->schemaForType($property->getType(), $property, $profile);
            $description = $this->firstAttribute($property, Description::class);
            $example     = $this->firstAttribute($property, Example::class);
            $source      = $this->sourceForProfile($property, $profile);

            if ($description instanceof Description) {
                $schema['description'] = $description->description;
            }

            if ($example instanceof Example) {
                $schema['example'] = $example->example;
            }

            if ($source instanceof Source && $source->name !== $property->getName()) {
                $schema['x-joomla-source'] = $source->name;
            }

            if (\array_key_exists($property->getName(), $defaults)) {
                $default = $defaults[$property->getName()];

                if (\is_scalar($default) || $default === null || \is_array($default)) {
                    $schema['default'] = $default;
                }
            }

            if ($guarded && \in_array($profile, [ResourceProfile::READ, ResourceProfile::LIST], true)) {
                $schema['readOnly'] = true;
            }

            if ($writeOnly && \in_array($profile, [ResourceProfile::CREATE, ResourceProfile::UPDATE], true)) {
                $schema['writeOnly'] = true;
            }

            $properties[$property->getName()] = $schema;

            if ($this->isRequired($property, $profile, $isResource)) {
                $required[] = $property->getName();
            }
        }

        $schema = [
            'type'                 => 'object',
            'additionalProperties' => $this->allowsAdditionalProperties($reflection),
            'properties'           => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        if ($isResource && $profile === ResourceProfile::UPDATE) {
            $schema['minProperties'] = 1;
        }

        return $schema;
    }

    private function isRequired(\ReflectionProperty $property, string $profile, bool $isResource): bool
    {
        $required = $this->firstAttribute($property, Required::class);
        $optional = $this->firstAttribute($property, Optional::class);

        if ($required instanceof Required && $required->appliesTo($profile)) {
            return true;
        }

        if ($optional instanceof Optional && $optional->appliesTo($profile)) {
            return false;
        }

        if (!$isResource) {
            return !$property->hasDefaultValue();
        }

        return match ($profile) {
            ResourceProfile::CREATE                      => !$property->hasDefaultValue(),
            ResourceProfile::READ, ResourceProfile::LIST => !$this->allowsNull($property->getType()),
            default                                      => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForType(?\ReflectionType $type, \ReflectionProperty $property, string $profile): array
    {
        if ($type instanceof \ReflectionUnionType) {
            $schemas = [];

            foreach ($type->getTypes() as $unionType) {
                $schemas[] = $this->schemaForNamedType($unionType, $property, $profile);
            }

            return ['anyOf' => $schemas];
        }

        if (!$type instanceof \ReflectionNamedType) {
            return [];
        }

        $schema = $this->schemaForNamedType($type, $property, $profile);

        if ($type->allowsNull() && ($schema['type'] ?? null) !== 'null') {
            if (isset($schema['type']) && \is_string($schema['type'])) {
                $schema['type'] = [$schema['type'], 'null'];
            } else {
                $schema = ['anyOf' => [$schema, ['type' => 'null']]];
            }
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForNamedType(
        \ReflectionNamedType $type,
        \ReflectionProperty $property,
        string $profile,
    ): array {
        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            return match ($typeName) {
                'int'    => ['type' => 'integer'],
                'float'  => ['type' => 'number'],
                'bool'   => ['type' => 'boolean'],
                'string' => ['type' => 'string'],
                'array'  => [
                    'type'  => 'array',
                    'items' => $this->arrayItemSchema($property, $profile),
                ],
                'object' => ['type' => 'object'],
                'null'   => ['type' => 'null'],
                default  => [],
            };
        }

        if (is_a($typeName, \DateTimeInterface::class, true)) {
            return ['type' => 'string', 'format' => 'date-time'];
        }

        if (enum_exists($typeName)) {
            $values = [];

            foreach ($typeName::cases() as $case) {
                $values[] = $case instanceof \BackedEnum ? $case->value : $case->name;
            }

            return [
                'type' => isset($values[0]) && \is_int($values[0]) ? 'integer' : 'string',
                'enum' => $values,
            ];
        }

        if (is_a($typeName, ResourceInterface::class, true)) {
            return $this->create($typeName, $profile);
        }

        return ['type' => 'object'];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayItemSchema(\ReflectionProperty $property, string $profile): array
    {
        foreach ($property->getAttributes(Items::class) as $attribute) {
            $items = $attribute->newInstance();

            if (!$items->appliesTo($profile)) {
                continue;
            }

            return $this->schemaForItemType($items->type, $profile);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForItemType(string $type, string $profile): array
    {
        return match ($type) {
            'integer', 'number', 'string', 'boolean', 'object' => ['type' => $type],
            default                                            => class_exists($type) ? $this->create($type, $profile) : [],
        };
    }

    private function allowsAdditionalProperties(\ReflectionClass $reflection): bool
    {
        $attributes = $reflection->getAttributes(AdditionalProperties::class);

        return $attributes === [] ? false : $attributes[0]->newInstance()->allowed;
    }

    private function allowsNull(?\ReflectionType $type): bool
    {
        return $type?->allowsNull() ?? true;
    }


    private function sourceForProfile(\ReflectionProperty $property, string $profile): ?Source
    {
        foreach ($property->getAttributes(Source::class) as $attribute) {
            $source = $attribute->newInstance();

            if ($source->appliesTo($profile)) {
                return $source;
            }
        }

        return null;
    }

    private function firstAttribute(\ReflectionProperty $property, string $attributeClass): ?object
    {
        $attributes = $property->getAttributes($attributeClass);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }
}
