<?php

/**
 * @package     Joomla.Libraries
 * @subpackage  OAuth.ResourceServer
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\CMS\OAuth\ResourceServer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Immutable principal derived from a validated OAuth access token.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class AccessTokenPrincipal
{
    /**
     * @param  list<string>  $audiences              Token audiences.
     * @param  list<string>  $scopes                 Granted OAuth scopes.
     * @param  list<string>  $authenticationMethods  Authentication method references.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public string $issuer,
        public string $subject,
        public string $clientId,
        public array $audiences,
        public array $scopes,
        public ?\DateTimeImmutable $issuedAt,
        public \DateTimeImmutable $expiresAt,
        public ?string $tokenId = null,
        public ?\DateTimeImmutable $authenticatedAt = null,
        public array $authenticationMethods = [],
    ) {
    }

    /**
     * Checks whether the token contains a scope.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function hasScope(string $scope): bool
    {
        return \in_array($scope, $this->scopes, true);
    }

    /**
     * Checks whether the token contains all requested scopes.
     *
     * @param  list<string>  $scopes  Required scopes.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function hasScopes(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }
}
