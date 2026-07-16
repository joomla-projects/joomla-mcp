<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Resource;

/**
 * Base class for typed web service resources.
 *
 * Uninitialised typed properties deliberately represent values which were not supplied. This preserves the
 * distinction between an omitted property and a property explicitly supplied with a null value during PATCH requests.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class Resource implements ResourceInterface
{
    /** @var array<string, mixed> */
    private array $additionalProperties = [];

    public static function from(
        mixed $from,
        string $profile = ResourceProfile::READ,
    ): ResourceInterface {
        if (\is_array($from)) {
            return static::fromArray($from, $profile);
        }

        if (\is_object($from)) {
            return static::fromObject($from, $profile);
        }

        throw new \InvalidArgumentException('A resource can only be created from an array or an object.');
    }

    public static function fromArray(
        array $array,
        string $profile = ResourceProfile::READ,
    ): ResourceInterface {
        return (new ResourceHydrator())->hydrate(static::class, $array, $profile);
    }

    public static function fromObject(
        object $object,
        string $profile = ResourceProfile::READ,
    ): ResourceInterface {
        return static::fromArray(get_object_vars($object), $profile);
    }

    public function toArray(string $profile = ResourceProfile::READ): array
    {
        return (new ResourceNormaliser())->normalise($this, $profile);
    }

    /**
     * Stores an installation-specific property which is not declared by the resource class.
     *
     * @since  __DEPLOY_VERSION__
     */
    final public function setAdditionalProperty(string $name, mixed $value): void
    {
        $this->additionalProperties[$name] = $value;
    }

    /**
     * Returns installation-specific properties which are not declared by the resource class.
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    final public function getAdditionalProperties(): array
    {
        return $this->additionalProperties;
    }

    /**
     * Tests whether a property was supplied or populated.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function has(string $property): bool
    {
        if (!property_exists($this, $property)) {
            return false;
        }

        return (new \ReflectionProperty($this, $property))->isInitialized($this);
    }

    /**
     * Returns only properties which have been supplied or populated.
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getProvidedProperties(string $profile = ResourceProfile::UPDATE): array
    {
        return $this->toArray($profile);
    }
}
