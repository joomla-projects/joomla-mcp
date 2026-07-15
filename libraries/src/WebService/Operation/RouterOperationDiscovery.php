<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

use Joomla\CMS\Router\ApiRouter;
use Joomla\Router\Route;

/**
 * Discovers contract operations backed by routes registered in Joomla's API router.
 *
 * Web services plugins remain the authority for whether an endpoint is enabled. A compiled operation is exposed only
 * when a matching route is present in the populated router. Routes projected from contracts carry their operation
 * directly; legacy routes are resolved by Joomla naming conventions and intersected with compiled operations.
 *
 * @since  __DEPLOY_VERSION__
 */
final class RouterOperationDiscovery
{
    /** @var list<OperationDefinition>|null */
    private ?array $operations = null;

    public function __construct(
        private readonly ApiRouter               $router,
        private readonly OperationCompiler       $compiler,
        private readonly ControllerClassResolver $controllerResolver,
    )
    {
    }

    /**
     * Returns operations represented by the router's currently registered routes.
     *
     * @return  list<OperationDefinition>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function discover(): array
    {
        if ($this->operations !== null) {
            return $this->operations;
        }

        $routes = $this->router->getRoutes();
        $operations = [];
        $controllerClasses = [];

        foreach ($routes as $route) {
            if ($route instanceof OperationRouteInterface) {
                $operation = $this->attachRouteDefaults($route->getOperation(), $route);
                $operations[$operation->operationId] = $operation;

                continue;
            }

            $controllerClass = $this->controllerResolver->resolve($route);

            if ($controllerClass !== null) {
                $controllerClasses[$controllerClass] = true;
            }
        }

        foreach (array_keys($controllerClasses) as $controllerClass) {
            foreach ($this->compiler->compile($controllerClass) as $operation) {
                if (isset($operations[$operation->operationId])) {
                    continue;
                }

                $route = $this->findMatchingRoute($operation, $routes);

                if ($route === null) {
                    continue;
                }

                $operations[$operation->operationId] = $this->attachRouteDefaults($operation, $route);
            }
        }

        return $this->operations = array_values($operations);
    }

    /**
     * @param list<Route> $routes Registered routes.
     *
     * @since   __DEPLOY_VERSION__
     */
    private function findMatchingRoute(OperationDefinition $operation, array $routes): ?Route
    {
        $component = $operation->acl['component'] ?? null;
        $target = $operation->controller . '.' . $operation->task;
        $path = trim($operation->path, '/');

        foreach ($routes as $route) {
            $defaults = $route->getDefaults();

            if (($defaults['component'] ?? null) !== $component) {
                continue;
            }

            if ($route->getController() !== $target || trim($route->getPattern(), '/') !== $path) {
                continue;
            }

            if (!\in_array($operation->method, $route->getMethods(), true)) {
                continue;
            }

            return $route;
        }

        return null;
    }

    private function attachRouteDefaults(OperationDefinition $operation, Route $route): OperationDefinition
    {
        $defaults = $route->getDefaults();

        unset($defaults['component'], $defaults['public']);

        return $operation->withRouteDefaults($defaults);
    }
}
