<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

/**
 * Maps canonical MCP-style arguments to REST path, query and request-body input.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationArgumentMapper
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @since  __DEPLOY_VERSION__
     */
    public function map(OperationDefinition $operation, array $arguments): OperationInput
    {
        $path = [];
        $query = [];
        $body = [];

        foreach ($operation->pathParameters as $transportName => $parameter) {
            $argumentName = $parameter['argument'] ?? $transportName;

            if (array_key_exists($argumentName, $arguments)) {
                $path[$transportName] = $arguments[$argumentName];
            }
        }

        foreach ($operation->queryParameters as $transportName => $parameter) {
            $argumentName = $parameter['argument'] ?? $transportName;

            if (array_key_exists($argumentName, $arguments)) {
                $query[$transportName] = $arguments[$argumentName];
            }
        }

        foreach ($operation->requestBodySchema['properties'] ?? [] as $name => $schema) {
            if (array_key_exists($name, $arguments)) {
                $body[$name] = $arguments[$name];
            }
        }

        return new OperationInput($path, $query, $body);
    }
}
