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
 * Projects canonical operations to Joomla router routes.
 *
 * @since  __DEPLOY_VERSION__
 */
final class RestRouteFactory
{
    public function create(OperationDefinition $operation): Route
    {
        $defaults = ['component' => $operation->acl['component'] ?? null];

        if ($operation->method === 'GET') {
            $defaults['public'] = $operation->public;
        }

        return new Route(
            [$operation->method],
            $operation->path,
            $operation->controller . '.' . $operation->task,
            isset($operation->pathParameters['id']) ? ['id' => '(\\d+)'] : [],
            array_filter($defaults, static fn (mixed $value): bool => $value !== null),
        );
    }
}
