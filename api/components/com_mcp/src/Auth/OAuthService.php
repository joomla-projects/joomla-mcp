<?php

/**
 * @package         Joomla.API
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Auth;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\MCP\Administrator\Model\AccessTokenModel;
use Joomla\Component\MCP\Administrator\Model\OAuthClientModel;
use Joomla\Component\MCP\Administrator\Model\OAuthCodeModel;
use Psr\Http\Message\ServerRequestInterface;
use Random\RandomException;

/**
 * OAuth service for MCP server authentication
 *
 * @since  __DEPLOY_VERSION__
 */
class OAuthService
{
    public function __construct(
        private readonly AccessTokenModel $accessTokenModel,
        private readonly OAuthCodeModel $authCodeModel,
        private readonly OAuthClientModel $clientModel
    ) {
    }

    private const string CLIENT_ID         = 'joomla-mcp-server';
    private const int CODE_EXPIRY_SECONDS  = 600; // 10 minutes
    private const int TOKEN_EXPIRY_SECONDS = 2592000; // 30 days

    /**
     * Generate authorisation URL for OAuth flow
     *
     * @param   string  $baseUrl          Base URL of the MCP server
     * @param   string  $clientName       Client name
     * @param   string  $redirectUri      Redirect URI
     * @param   string  $codeChallenge    PKCE code challenge
     * @param   string  $challengeMethod  PKCE code challenge method
     * @param   string  $state            State parameter
     *
     * @return  string  Authorisation URL
     * @since   __DEPLOY_VERSION__
     */
    public function generateAuthorizationUrl(string $baseUrl, string $clientName = '', string $redirectUri = '', string $codeChallenge = '', string $challengeMethod = 'S256', string $state = ''): string
    {
        $params = [
            'client_id'     => self::CLIENT_ID,
            'response_type' => 'code',
            'client_name'   => $clientName,
        ];

        if (!empty($redirectUri)) {
            $params['redirect_uri'] = $redirectUri;
        }

        if (!empty($codeChallenge)) {
            $params['code_challenge']        = $codeChallenge;
            $params['code_challenge_method'] = $challengeMethod;
        }

        if (!empty($state)) {
            $params['state'] = $state;
        }

        return rtrim($baseUrl, '/') . '/mcp_oauth/authorize?' . http_build_query($params);
    }

    /**
     * Create authorisation code for authenticated user
     *
     * @param int $userid User ID
     * @param string $clientName Client name
     * @param string $redirectUri Redirect URI
     * @param string $pkceChallenge PKCE code challenge
     * @param string $challengeMethod PKCE code challenge method
     *
     * @return string  Authorisation code
     * @throws RandomException if a secure token cannot be generated
     * @since   __DEPLOY_VERSION__
     */
    public function createAuthorizationCode(int $userid, string $clientName, string $redirectUri = '', string $pkceChallenge = '', string $challengeMethod = 'S256'): string
    {
        $code    = $this->generateSecureToken();
        $expires = time() + self::CODE_EXPIRY_SECONDS;

        $this->authCodeModel->store([
            'pid'                   => 0,
            'tstamp'                => time(),
            'crdate'                => time(),
            'code'                  => $code,
            'userid'                => $userid,
            'client_name'           => $clientName,
            'pkce_challenge'        => $pkceChallenge,
            'pkce_challenge_method' => $challengeMethod,
            'redirect_uri'          => $redirectUri,
            'expires'               => $expires,
        ]);

        return $code;
    }

    /**
     * Exchange authorisation code for access token
     *
     * @param string $code Authorisation code
     * @param string|null $codeVerifier PKCE code verifier
     * @param ServerRequestInterface|null $request Server request object
     *
     * @return array|null  Access token response data or null on failure
     * @throws RandomException if a secure token cannot be generated
     * @since   __DEPLOY_VERSION__
     */
    public function exchangeCodeForToken(string $code, ?string $codeVerifier = null, ?ServerRequestInterface $request = null): ?array
    {
        $authCode = $this->authCodeModel->getByCode($code, time());

        if (!$authCode) {
            return null;
        }

        // Verify PKCE challenge if provided
        if (!empty($authCode['pkce_challenge']) && $codeVerifier !== null) {
            $computedChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
            if ($computedChallenge !== $authCode['pkce_challenge']) {
                return null;
            }
        }

        // Generate access token
        $accessToken = $this->generateSecureToken();
        $expires     = time() + self::TOKEN_EXPIRY_SECONDS;

        // Get client IP
        $clientIp = '';
        if ($request !== null) {
            $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
        }

        $this->accessTokenModel->store(
            [
                'pid'          => 0,
                'tstamp'       => time(),
                'crdate'       => time(),
                'token'        => $accessToken,
                'userid'       => $authCode['userid'],
                'client_name'  => $authCode['client_name'],
                'expires'      => $expires,
                'last_used'    => time(),
                'created_ip'   => $clientIp,
                'last_used_ip' => $clientIp,
            ]
        );

        $this->authCodeModel->deleteByUid($authCode['uid']);

        return [
            'access_token' => $accessToken,
            'token_type'   => 'Bearer',
            'expires_in'   => self::TOKEN_EXPIRY_SECONDS,
        ];
    }

    /**
     * Validate access token and return user info
     *
     * @param   string  $token  Access token
     * @param   ServerRequestInterface|null  $request  Server request object
     *
     * @return  array|null  User info or null on failure
     * @since   __DEPLOY_VERSION__
     */
    public function validateToken(string $token, ?ServerRequestInterface $request = null): ?array
    {
        $tokenRecord  = $this->accessTokenModel->getByToken($token);

        if (!$tokenRecord) {
            return null;
        }

        // Update last used timestamp and IP
        $clientIp = '';
        if ($request !== null) {
            $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
        }

        $this->accessTokenModel->updateUsage($tokenRecord['uid'], $clientIp);

        return [
            'be_user_uid' => (int)$tokenRecord['be_user_uid'],
            'client_name' => $tokenRecord['client_name'],
            'token_uid'   => (int)$tokenRecord['uid'],
        ];
    }

    /**
     * Get all active tokens for a user
     */
    public function getUserTokens(int $beUserId): array
    {
        return $this->accessTokenModel->getByUserid($beUserId) ?: [];
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(int $tokenUid, int $userid): void
    {
        $this->accessTokenModel->revoke($tokenUid, $userid);
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userid): void
    {
        $this->accessTokenModel->revokeAllForUser($userid);
    }

    /**
     * Clean up expired codes and tokens
     */
    public function cleanupExpired(): void
    {
        $currentTime = time();

        $this->authCodeModel->deleteExpired($currentTime);
        $this->accessTokenModel->deleteExpired($currentTime);
    }

    /**
     * Register a new OAuth client dynamically
     * @throws RandomException if random bytes cannot be generated
     */
    public function registerClient(array $clientData): array
    {
        // Generate client credentials
        $clientId     = 'mcp_client_' . bin2hex(random_bytes(16));
        $clientSecret = bin2hex(random_bytes(32));

        $this->clientModel->store([
            'pid'           => 0,
            'tstamp'        => time(),
            'crdate'        => time(),
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'client_name'   => $clientData['client_name'] ?? 'MCP Client',
            'redirect_uris' => json_encode($clientData['redirect_uris'] ?? []),
            'grant_types'   => json_encode($clientData['grant_types'] ?? ['authorization_code']),
            'scope'         => $clientData['scope'] ?? 'mcp_access',
        ]);


        return [
            'client_id'      => $clientId,
            'client_secret'  => $clientSecret,
            'client_name'    => $clientData['client_name'] ?? 'MCP Client',
            'grant_types'    => $clientData['grant_types'] ?? ['authorization_code'],
            'response_types' => ['code'],
            'scope'          => $clientData['scope'] ?? 'mcp_access',
            'redirect_uris'  => $clientData['redirect_uris'] ?? ['http://localhost'],
        ];
    }

    /**
     * Get OAuth metadata for discovery
     */
    public function getMetadata(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');

        return [
            'issuer'                                       => $baseUrl,
            'authorization_endpoint'                       => $baseUrl . '/mcp_oauth/authorize',
            'token_endpoint'                               => $baseUrl . '/mcp_oauth/token',
            'registration_endpoint'                        => $baseUrl . '/mcp_oauth/register',
            'response_types_supported'                     => ['code'],
            'grant_types_supported'                        => ['authorization_code'],
            'code_challenge_methods_supported'             => ['S256', 'plain'],
            'token_endpoint_auth_methods_supported'        => ['none', 'client_secret_post'],
            'registration_endpoint_auth_methods_supported' => ['none'],
        ];
    }

    /**
     * Create access token directly (bypassing authorization code flow)
     * @throws RandomException if random bytes cannot be generated
     */
    public function createDirectAccessToken(int $beUserId, string $clientName, ?ServerRequestInterface $request = null): string
    {
        $accessToken = $this->generateSecureToken();
        $expires     = time() + self::TOKEN_EXPIRY_SECONDS;

        // Get client IP
        $clientIp = '';
        if ($request !== null) {
            $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
        }

        $this->accessTokenModel->store([
            'pid'          => 0,
            'tstamp'       => time(),
            'crdate'       => time(),
            'token'        => $accessToken,
            'be_user_uid'  => $beUserId,
            'client_name'  => $clientName,
            'expires'      => $expires,
            'last_used'    => time(),
            'created_ip'   => $clientIp,
            'last_used_ip' => $clientIp,
        ]);

        return $accessToken;
    }

    /**
     * Generate cryptographically secure token
     * @throws RandomException
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
