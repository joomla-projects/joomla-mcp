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

use JCrafts\Component\JCraftsoauth2server\Administrator\Server\OAuth2ServerFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;

/**
 * Validates MCP bearer tokens against the jCrafts OAuth2 Server (com_jcraftsoauth2server).
 *
 * @since  __DEPLOY_VERSION__
 */
final class JCraftsOAuth2AuthService implements AuthServiceInterface
{
    /**
     * Constructor.
     *
     * @param DatabaseInterface     $db          Database connector
     * @param UserFactoryInterface  $userFactory User factory
     *
     * @since __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly UserFactoryInterface $userFactory
    ) {
        // com_jcraftsoauth2server bundles its own League OAuth2 Server vendor tree, which is not
        // registered as a Joomla PSR-4 library — load it here so OAuth2ServerFactory and the League
        // classes below are available, without requiring callers to know this implementation detail.
        require_once JPATH_LIBRARIES . '/oauth2server4jcrafts/src/vendor/autoload.php';
    }

    /**
     * Validate an access token issued by the jCrafts OAuth2 Server
     *
     * @param string $token Access token
     * @return TokenInfo|null The token information. Null if the token is invalid or expired.
     */
    public function validateToken(?string $token): ?TokenInfo
    {
        if ($token === null || $token === '') {
            return null;
        }

        $publicKey = trim((string) ComponentHelper::getParams('com_jcraftsoauth2server')->get('public_key', ''));

        if ($publicKey === '') {
            return null;
        }

        $resourceServer = (new OAuth2ServerFactory($this->db))->createResourceServer($publicKey);

        $request = new ServerRequest([], [], null, null, 'php://input', ['Authorization' => 'Bearer ' . $token]);

        try {
            $validatedRequest = $resourceServer->validateAuthenticatedRequest($request);
        } catch (OAuthServerException) {
            return null;
        }

        $userId = (int) $validatedRequest->getAttribute('oauth_user_id');

        if ($userId <= 0) {
            return null;
        }

        $user = $this->userFactory->loadUserById($userId);

        if (!$user->id || $user->block || !empty(trim((string) $user->activation)) || $user->requireReset) {
            return null;
        }

        if (!$user->authorise('mcp.access', 'com_mcp')) {
            return null;
        }

        return TokenInfo::fromOAuth2($userId, (string) $validatedRequest->getAttribute('oauth_client_id', ''));
    }
}
