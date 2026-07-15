<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

use Joomla\Router\Route;

/**
 * Joomla router route carrying the canonical operation from which it was projected.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationRoute extends Route implements OperationRouteInterface
{
    /**
     * @param  string[]                 $methods    HTTP methods supported by the route.
     * @param  string                   $pattern    Route pattern.
     * @param  mixed                    $controller Controller target.
     * @param  array<string, string>    $rules      Route variable rules.
     * @param  array<string, mixed>     $defaults   Route defaults.
     * @param  OperationDefinition      $operation  Canonical operation represented by the route.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        array $methods,
        string $pattern,
        mixed $controller,
        array $rules,
        array $defaults,
        private OperationDefinition $operation,
    ) {
        parent::__construct($methods, $pattern, $controller, $rules, $defaults);
    }

    public function getOperation(): OperationDefinition
    {
        return $this->operation;
    }

    /**
     * Serialises the route together with its operation metadata.
     *
     * @return  array<string, mixed>
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __serialize(): array
    {
        return parent::__serialize() + ['operation' => $this->operation];
    }

    /**
     * Restores the route together with its operation metadata.
     *
     * @param   array<string, mixed>  $data  Serialised route data.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __unserialize(array $data): void
    {
        $this->operation = $data['operation'];

        unset($data['operation']);

        parent::__unserialize($data);
    }
}
