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

use Joomla\Component\MCP\Administrator\Model\McpModel;

/**
 * OAuth service for MCP server authentication
 *
 * @since  __DEPLOY_VERSION__
 */
class DemoAuthService implements AuthServiceInterface
{
    /**
     * Constructor.
     *
     * @param McpModel $tokenModel Access token model
     *
     * @since __DEPLOY_VERSION__
     */
    public function __construct(private readonly McpModel $tokenModel)
    {
    }

    /**
     * Validate an access token
     *
     * @param string|null $token Access token
     * @return TokenInfo|null  The token information. Null if the token is invalid or expired.
     * @throws \DateMalformedStringException
     */
    public function validateToken(?string $token): ?TokenInfo
    {
        if ($token === null) {
            return null;
        }

        $tokenInfo = $this->tokenModel->getByToken($token);

        if ($tokenInfo === null) {
            return null;
        }

        return TokenInfo::fromArray($tokenInfo);
    }
}
