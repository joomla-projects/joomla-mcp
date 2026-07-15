<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Joomla\CMS\WebService\Operation\Attribute\ResourceOperations;
use Joomla\Router\Route;

/**
 * Resolves convention-based Joomla API controller classes from registered routes.
 *
 * The resolver is deliberately conservative. Routes that do not follow Joomla's component and controller naming
 * conventions remain valid REST routes, but they are not exposed as generated MCP tools.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ControllerClassResolver
{
    private readonly Inflector $inflector;

    public function __construct(?Inflector $inflector = null)
    {
        $this->inflector = $inflector ?? InflectorFactory::create()->build();
    }

    /**
     * Resolves an attributed API controller represented by the supplied route.
     *
     * @param   Route  $route  Registered Joomla route.
     *
     * @return  class-string|null
     *
     * @since   __DEPLOY_VERSION__
     */
    public function resolve(Route $route): ?string
    {
        $defaults = $route->getDefaults();
        $component = $defaults['component'] ?? null;
        $target = $route->getController();

        if (!\is_string($component) || !str_starts_with($component, 'com_') || !\is_string($target)) {
            return null;
        }

        $targetParts = explode('.', $target, 2);

        if (\count($targetParts) !== 2 || $targetParts[0] === '') {
            return null;
        }

        $componentName = $this->inflector->classify(substr($component, 4));
        $controllerName = $this->inflector->classify($targetParts[0]);
        $controllerClass = \sprintf(
            'Joomla\\Component\\%s\\Api\\Controller\\%sController',
            $componentName,
            $controllerName,
        );

        if (!class_exists($controllerClass)) {
            return null;
        }

        $reflection = new \ReflectionClass($controllerClass);

        if ($reflection->getAttributes(ResourceOperations::class) === []) {
            return null;
        }

        return $controllerClass;
    }
}
