<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Auth;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Tries multiple authentication services in order, returning the first successful result.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ChainAuthService implements AuthServiceInterface
{
    /**
     * @var AuthServiceInterface[]
     */
    private readonly array $services;

    /**
     * Constructor.
     *
     * @param AuthServiceInterface ...$services Authentication services to try, in order.
     *
     * @since __DEPLOY_VERSION__
     */
    public function __construct(AuthServiceInterface ...$services)
    {
        $this->services = $services;
    }

    /**
     * Validate a token against each configured authentication service until one succeeds
     *
     * @param string|null $token Access token
     * @return TokenInfo|null The token information. Null if no service could validate the token.
     */
    public function validateToken(?string $token): ?TokenInfo
    {
        foreach ($this->services as $service) {
            $tokenInfo = $service->validateToken($token);

            if ($tokenInfo !== null) {
                return $tokenInfo;
            }
        }

        return null;
    }
}
