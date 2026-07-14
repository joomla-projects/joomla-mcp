<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Attribute\Property\Hidden;
use Joomla\CMS\WebService\Resource\Attribute\Property\WriteOnly;

/**
 * Normalises typed resources to profile-specific serialisable arrays.
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
        string $profile = ResourceProfile::READ,
    ): array
    {
        $data = $resource instanceof Resource
            ? $this->normaliseValue($resource->getAdditionalProperties(), $profile)
            : [];
        $reflection = new \ReflectionObject($resource);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (
                $property->isStatic()
                || !$property->isInitialized($resource)
                || !$this->isVisible($property, $profile)
            ) {
                continue;
            }

            $data[$property->getName()] = $this->normaliseValue($property->getValue($resource), $profile);
        }

        return $data;
    }

    private function isVisible(\ReflectionProperty $property, string $profile): bool
    {
        $hiddenAttributes = $property->getAttributes(Hidden::class);

        if ($hiddenAttributes !== [] && $hiddenAttributes[0]->newInstance()->appliesTo($profile)) {
            return false;
        }

        if (
            \in_array($profile, [ResourceProfile::READ, ResourceProfile::LIST], true)
            && $property->getAttributes(WriteOnly::class) !== []
        ) {
            return false;
        }

        return !(
            \in_array($profile, [ResourceProfile::CREATE, ResourceProfile::UPDATE], true)
            && $property->getAttributes(Guarded::class) !== []
        );
    }

    private function normaliseValue(mixed $value, string $profile): mixed
    {
        if ($value instanceof ResourceInterface) {
            return $this->normalise($value, $profile);
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
                fn (mixed $item): mixed => $this->normaliseValue($item, $profile),
                $value,
            );
        }

        return $value;
    }
}
