<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\WebService\Operation\OperationDefinition;

/**
 * Derives resource scopes from canonical operation identifiers by convention.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationScopeResolver
{
    /**
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function resolve(OperationDefinition $operation): array
    {
        $separator = strrpos($operation->operationId, '.');

        if ($separator === false) {
            return [$operation->operationId . ':use'];
        }

        $resource = substr($operation->operationId, 0, $separator);
        $action   = substr($operation->operationId, $separator + 1);
        $suffix   = match ($action) {
            'list', 'get'      => 'read',
            'create', 'update' => 'write',
            'delete'           => 'delete',
            default            => $action,
        };

        return [$resource . ':' . $suffix];
    }
}
