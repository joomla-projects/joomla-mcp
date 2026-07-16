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

use Joomla\CMS\OAuth\ResourceServer\AccessTokenPrincipal;
use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;
use Joomla\CMS\User\User;

/**
 * Authenticated request context passed to MCP tools and internal operations.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class McpRequestContext
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public AccessTokenPrincipal $principal,
        public User $user,
        public ResourceIdentifier $resource,
        public string $requestId,
    ) {
    }
}
