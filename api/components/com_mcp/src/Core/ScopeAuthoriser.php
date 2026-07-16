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
use Joomla\Component\MCP\Api\Tool\ScopedAbilityInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;

/**
 * Applies OAuth scope checks to MCP requests and abilities.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ScopeAuthoriser
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(private string $baseScope = 'mcp:use')
    {
    }

    /**
     * Ensures that the principal is permitted to use the MCP resource.
     *
     * @throws  InsufficientScopeException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function assertBaseAccess(AccessTokenPrincipal $principal): void
    {
        $this->assertScopes($principal, [$this->baseScope]);
    }

    /**
     * Ensures that the principal can invoke the tool.
     *
     * @throws  InsufficientScopeException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function assertToolAccess(AccessTokenPrincipal $principal, ToolInterface $tool): void
    {
        $this->assertScopes($principal, $this->requiredScopes($tool));
    }

    /**
     * Checks whether the principal can discover or invoke the tool.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function canUseTool(AccessTokenPrincipal $principal, ToolInterface $tool): bool
    {
        return $principal->hasScopes($this->requiredScopes($tool));
    }

    /**
     * Returns the scopes required for the tool, including the base MCP scope.
     *
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function requiredScopes(ToolInterface $tool): array
    {
        $scopes = [$this->baseScope];

        if ($tool instanceof ScopedAbilityInterface) {
            $scopes = [...$scopes, ...$tool->getRequiredScopes()];
        }

        return array_values(array_unique(array_filter($scopes)));
    }

    /**
     * Returns all scopes advertised by the current ability registry.
     *
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function supportedScopes(AbilityRegistry $registry): array
    {
        $scopes = [$this->baseScope];

        foreach ($registry->getTools() as $tool) {
            $scopes = [...$scopes, ...$this->requiredScopes($tool)];
        }

        return array_values(array_unique(array_filter($scopes)));
    }

    /**
     * @param  list<string>  $scopes  Required scopes.
     *
     * @throws  InsufficientScopeException
     *
     * @since  __DEPLOY_VERSION__
     */
    private function assertScopes(AccessTokenPrincipal $principal, array $scopes): void
    {
        $missing = array_values(
            array_filter($scopes, static fn (string $scope): bool => !$principal->hasScope($scope)),
        );

        if ($missing !== []) {
            throw new InsufficientScopeException($missing);
        }
    }
}
