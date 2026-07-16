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
use Joomla\Component\MCP\Api\Auth\JoomlaSubjectResolverInterface;

/**
 * Creates an authenticated MCP context from a validated OAuth principal.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class McpRequestContextFactory
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private JoomlaSubjectResolverInterface $subjectResolver,
        private string $accessAction = 'mcp.access',
    ) {
    }

    /**
     * Creates the context and applies the coarse Joomla MCP access check.
     *
     * @throws  McpAccessDeniedException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(
        AccessTokenPrincipal $principal,
        ResourceIdentifier $resource,
        string $requestId,
    ): McpRequestContext {
        $user = $this->subjectResolver->resolve($principal->issuer, $principal->subject);

        if (!$user->authorise($this->accessAction, 'com_mcp')) {
            throw new McpAccessDeniedException('The Joomla user is not permitted to access the MCP server.');
        }

        return new McpRequestContext($principal, $user, $resource, $requestId);
    }
}
