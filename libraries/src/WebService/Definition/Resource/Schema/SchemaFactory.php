<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Definition\Resource\Schema;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Example;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Definition\Attribute\Property\Items;
use Joomla\CMS\WebService\Definition\Attribute\Property\Optional;
use Joomla\CMS\WebService\Definition\Attribute\Property\Required;
use Joomla\CMS\WebService\Definition\Attribute\Resource\AdditionalProperties;
use Joomla\CMS\WebService\Definition\Resource\ResourceInterface;
use Joomla\CMS\WebService\Operation\OperationType;

/**
 * Builds an operation specific resource description from typed DTOs and resource conventions.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SchemaFactory
{
    /**
     * @param class-string $className
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(string $className, OperationType $operationType = OperationType::READ): array
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
            $hidden    = $this->firstAttribute($property, Hidden::class);

            if ($hidden instanceof Hidden && $hidden->appliesTo($operationType)) {
                continue;
            }

            if (
                $isResource
                && $guarded
                && \in_array($operationType, [OperationType::CREATE, OperationType::UPDATE], true)
            ) {
                continue;
            }

            $propertySchema      = $this->getSchemaForProperty($property, $operationType);
            $description         = $this->firstAttribute($property, Description::class);
            $example             = $this->firstAttribute($property, Example::class);

            if ($description instanceof Description) {
                $propertySchema['description'] = $description->description;
            }

            if ($example instanceof Example) {
                $propertySchema['example'] = $example->example;
            }

            if (\array_key_exists($property->getName(), $defaults)) {
                $default = $defaults[$property->getName()];

                if (\is_scalar($default) || $default === null || \is_array($default)) {
                    $propertySchema['default'] = $default;
                }
            }

            if ($guarded && \in_array($operationType, [OperationType::READ, OperationType::LIST], true)) {
                $propertySchema['readOnly'] = true;
            }

            $properties[$property->getName()] = $propertySchema;

            if ($this->isRequired($property, $operationType)) {
                $required[] = $property->getName();
            }
        }

        $resourceSchema = [
            'type'                 => 'object',
            'additionalProperties' => $this->allowsAdditionalProperties($reflection),
            'properties'           => $properties,
        ];

        if ($required !== []) {
            $resourceSchema['required'] = $required;
        }

        if ($isResource && $operationType === OperationType::UPDATE) {
            $resourceSchema['minProperties'] = 1;
        }

        return $resourceSchema;
    }

    private function isRequired(\ReflectionProperty $property, OperationType $operationType): bool
    {
        $required = $this->firstAttribute($property, Required::class);
        $optional = $this->firstAttribute($property, Optional::class);

        if ($required instanceof Required && $required->appliesTo($operationType)) {
            return true;
        }

        if ($optional instanceof Optional && $optional->appliesTo($operationType)) {
            return false;
        }

        return match ($operationType) {
            OperationType::CREATE                    => !$property->hasDefaultValue(),
            OperationType::READ, OperationType::LIST => !$this->allowsNull($property->getType()),
            default                                  => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getSchemaForProperty(\ReflectionProperty $property, OperationType $operationType): array
    {
        $propertyType = $property->getType();
        if ($propertyType instanceof \ReflectionUnionType) {
            $schemas = [];

            foreach ($propertyType->getTypes() as $unionType) {
                $schemas[] = $this->schemaForNamedType($unionType, $property, $operationType);
            }

            return ['anyOf' => $schemas];
        }

        if (!$propertyType instanceof \ReflectionNamedType) {
            return [];
        }

        $schema = $this->schemaForNamedType($propertyType, $property, $operationType);

        if ($propertyType->allowsNull() && ($schema['type'] ?? null) !== 'null') {
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
        OperationType $operationType,
    ): array {
        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            return match ($typeName) {
                'int'    => ['type' => 'integer'],
                'float'  => ['type' => 'number'],
                'bool'   => ['type' => 'boolean'],
                'string' => ['type' => 'string'],
                'array'  => $this->arraySchema($property, $operationType),
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

        // A nested contract class is described by the same conventions. It need not be a Resource itself: a plain
        // value object such as ArticleUrls carries no identity and no guarded state, only typed properties.
        if (class_exists($typeName)) {
            return $this->create($typeName, $operationType);
        }

        return ['type' => 'object'];
    }

    /**
     * @return array<string, mixed>
     */
    private function arraySchema(\ReflectionProperty $property, OperationType $operationType): array
    {
        $schema = ['type' => 'array'];
        $items  = $this->arrayItemSchema($property, $operationType);

        // An empty PHP array encodes as a JSON list, which is not a valid items value. Untyped items are left out.
        if ($items !== []) {
            $schema['items'] = $items;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayItemSchema(\ReflectionProperty $property, OperationType $operationType): array
    {
        foreach ($property->getAttributes(Items::class) as $attribute) {
            $items = $attribute->newInstance();

            if (!$items->appliesTo($operationType)) {
                continue;
            }

            return $this->schemaForItemType($items->type, $operationType);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaForItemType(string $type, OperationType $operationType): array
    {
        return match ($type) {
            'integer', 'number', 'string', 'boolean', 'object' => ['type' => $type],
            default                                            => class_exists($type) ? $this->create($type, $operationType) : [],
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

    private function firstAttribute(\ReflectionProperty $property, string $attributeClass): ?object
    {
        $attributes = $property->getAttributes($attributeClass);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }
}
