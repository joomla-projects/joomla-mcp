<?php declare(strict_types=1);
/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Auth;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\MCP\Administrator\Model\McpModel;
use Psr\Http\Message\ServerRequestInterface;

/**
 * OAuth service for MCP server authentication
 *
 * @since  __DEPLOY_VERSION__
 */
class OAuthService
{
	public function __construct(private readonly McpModel $model)
	{
	}

	private const CLIENT_ID = 'joomla-mcp-server';
	private const CODE_EXPIRY_SECONDS = 600; // 10 minutes
	private const TOKEN_EXPIRY_SECONDS = 2592000; // 30 days

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
	 * @return string  Authorisation URL
	 * @since   __DEPLOY_VERSION__
	 */
	public function generateAuthorizationUrl(string $baseUrl, string $clientName = '', string $redirectUri = '', string $codeChallenge = '', string $challengeMethod = 'S256', string $state = ''): string
	{
		$params = [
			'client_id' => self::CLIENT_ID,
			'response_type' => 'code',
			'client_name' => $clientName,
		];

		if (!empty($redirectUri)) {
			$params['redirect_uri'] = $redirectUri;
		}

		if (!empty($codeChallenge)) {
			$params['code_challenge'] = $codeChallenge;
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
	 * @param   int     $userid           User ID
	 * @param   string  $clientName       Client name
	 * @param   string  $redirectUri      Redirect URI
	 * @param   string  $pkceChallenge    PKCE code challenge
	 * @param   string  $challengeMethod  PKCE code challenge method
	 *
	 * @return string  Authorisation code
	 * @since   __DEPLOY_VERSION__
	 */
	public function createAuthorizationCode(int $userid, string $clientName, string $redirectUri = '', string $pkceChallenge = '', string $challengeMethod = 'S256'): string
	{
		$code    = $this->generateSecureToken();
		$expires = time() + self::CODE_EXPIRY_SECONDS;

		$this->model->insertOAuthCode([
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
	 * @param   string                       $code          Authorisation code
	 * @param   string|null                  $codeVerifier  PKCE code verifier
	 * @param   ServerRequestInterface|null  $request       Server request object
	 *
	 * @return array|null  Access token response data or null on failure
	 * @since   __DEPLOY_VERSION__
	 */
	public function exchangeCodeForToken(string $code, ?string $codeVerifier = null, ?ServerRequestInterface $request = null): ?array
	{
		$authCode = $this->model->getOAuthCodeData($code, time());

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
		$expires = time() + self::TOKEN_EXPIRY_SECONDS;

		// Get client IP
		$clientIp = '';
		if ($request !== null) {
			$clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
		}

		$this->model->storeAccessToken(
			[
				'pid' => 0,
				'tstamp' => time(),
				'crdate' => time(),
				'token' => $accessToken,
				'userid' => $authCode['userid'],
				'client_name' => $authCode['client_name'],
				'expires' => $expires,
				'last_used' => time(),
				'created_ip' => $clientIp,
				'last_used_ip' => $clientIp,
			]
		);

		$this->model->removeAuthCode($authCode['uid']);

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
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		$queryBuilder = $connection->createQueryBuilder();
		$tokenRecord = $queryBuilder
			->select('*')
			->from('tx_mcpserver_access_tokens')
			->where(
				$queryBuilder->expr()->eq('token', $queryBuilder->createNamedParameter($token)),
				$queryBuilder->expr()->gt('expires', $queryBuilder->createNamedParameter(time())),
				$queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
			)
			->executeQuery()
			->fetchAssociative();

		if (!$tokenRecord) {
			return null;
		}

		// Update last used timestamp and IP
		$clientIp = '';
		if ($request !== null) {
			$clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
		}

		$queryBuilder = $connection->createQueryBuilder();
		$queryBuilder
			->update('tx_mcpserver_access_tokens')
			->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($tokenRecord['uid'])))
			->set('last_used', time())
			->set('last_used_ip', $clientIp)
			->executeStatement();

		return [
			'be_user_uid' => (int)$tokenRecord['be_user_uid'],
			'client_name' => $tokenRecord['client_name'],
			'token_uid' => (int)$tokenRecord['uid'],
		];
	}

	/**
	 * Get all active tokens for a user
	 */
	public function getUserTokens(int $beUserId): array
	{
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		$queryBuilder = $connection->createQueryBuilder();
		$tokens = $queryBuilder
			->select('*')
			->from('tx_mcpserver_access_tokens')
			->where(
				$queryBuilder->expr()->eq('be_user_uid', $queryBuilder->createNamedParameter($beUserId)),
				$queryBuilder->expr()->gt('expires', $queryBuilder->createNamedParameter(time())),
				$queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
			)
			->orderBy('crdate', 'DESC')
			->executeQuery()
			->fetchAllAssociative();

		return $tokens ?: [];
	}

	/**
	 * Revoke a specific token
	 */
	public function revokeToken(int $tokenUid, int $beUserId): bool
	{
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		$affectedRows = $connection->update(
			'tx_mcpserver_access_tokens',
			['deleted' => 1, 'tstamp' => time()],
			[
				'uid' => $tokenUid,
				'be_user_uid' => $beUserId,
			]
		);

		return $affectedRows > 0;
	}

	/**
	 * Revoke all tokens for a user
	 */
	public function revokeAllUserTokens(int $beUserId): int
	{
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		return $connection->update(
			'tx_mcpserver_access_tokens',
			['deleted' => 1, 'tstamp' => time()],
			['be_user_uid' => $beUserId]
		);
	}

	/**
	 * Clean up expired codes and tokens
	 */
	public function cleanupExpired(): void
	{
		$currentTime = time();

		// Clean up expired authorization codes
		$codeConnection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_oauth_codes');

		$codeConnection->delete(
			'tx_mcpserver_oauth_codes',
			['expires' => $codeConnection->createQueryBuilder()->expr()->lt('expires', $currentTime)]
		);

		// Mark expired tokens as deleted
		$tokenConnection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		$tokenConnection->update(
			'tx_mcpserver_access_tokens',
			['deleted' => 1, 'tstamp' => $currentTime],
			['expires' => $tokenConnection->createQueryBuilder()->expr()->lt('expires', $currentTime)]
		);
	}

	/**
	 * Register a new OAuth client dynamically
	 */
	public function registerClient(array $clientData): array
	{
		// Generate client credentials
		$clientId = 'mcp_client_' . bin2hex(random_bytes(16));
		$clientSecret = bin2hex(random_bytes(32));

		// For now, store in database (could be enhanced later)
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_oauth_clients');

		// Check if table exists, if not create it on the fly
		try {
			$connection->insert(
				'tx_mcpserver_oauth_clients',
				[
					'pid' => 0,
					'tstamp' => time(),
					'crdate' => time(),
					'client_id' => $clientId,
					'client_secret' => $clientSecret,
					'client_name' => $clientData['client_name'] ?? 'MCP Client',
					'redirect_uris' => json_encode($clientData['redirect_uris'] ?? []),
					'grant_types' => json_encode($clientData['grant_types'] ?? ['authorization_code']),
					'scope' => $clientData['scope'] ?? 'mcp_access',
				]
			);
		} catch (\Exception $e) {
			// If table doesn't exist, we'll use the fixed client approach for now
			return [
				'client_id' => self::CLIENT_ID,
				'client_name' => $clientData['client_name'] ?? 'MCP Client',
				'grant_types' => ['authorization_code'],
				'response_types' => ['code'],
				'scope' => 'mcp_access',
				'redirect_uris' => $clientData['redirect_uris'] ?? ['http://localhost'],
			];
		}

		return [
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
			'client_name' => $clientData['client_name'] ?? 'MCP Client',
			'grant_types' => $clientData['grant_types'] ?? ['authorization_code'],
			'response_types' => ['code'],
			'scope' => $clientData['scope'] ?? 'mcp_access',
			'redirect_uris' => $clientData['redirect_uris'] ?? ['http://localhost'],
		];
	}

	/**
	 * Get OAuth metadata for discovery
	 */
	public function getMetadata(string $baseUrl): array
	{
		$baseUrl = rtrim($baseUrl, '/');

		return [
			'issuer' => $baseUrl,
			'authorization_endpoint' => $baseUrl . '/mcp_oauth/authorize',
			'token_endpoint' => $baseUrl . '/mcp_oauth/token',
			'registration_endpoint' => $baseUrl . '/mcp_oauth/register',
			'response_types_supported' => ['code'],
			'grant_types_supported' => ['authorization_code'],
			'code_challenge_methods_supported' => ['S256', 'plain'],
			'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post'],
			'registration_endpoint_auth_methods_supported' => ['none'],
		];
	}

	/**
	 * Create access token directly (bypassing authorization code flow)
	 */
	public function createDirectAccessToken(int $beUserId, string $clientName, ?ServerRequestInterface $request = null): string
	{
		$accessToken = $this->generateSecureToken();
		$expires = time() + self::TOKEN_EXPIRY_SECONDS;

		// Get client IP
		$clientIp = '';
		if ($request !== null) {
			$clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? '';
		}

		// Create access token
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)
			->getConnectionForTable('tx_mcpserver_access_tokens');

		$connection->insert(
			'tx_mcpserver_access_tokens',
			[
				'pid' => 0,
				'tstamp' => time(),
				'crdate' => time(),
				'token' => $accessToken,
				'be_user_uid' => $beUserId,
				'client_name' => $clientName,
				'expires' => $expires,
				'last_used' => time(),
				'created_ip' => $clientIp,
				'last_used_ip' => $clientIp,
			]
		);

		return $accessToken;
	}

	/**
	 * Generate cryptographically secure token
	 */
	private function generateSecureToken(): string
	{
		return bin2hex(random_bytes(32));
	}
}
