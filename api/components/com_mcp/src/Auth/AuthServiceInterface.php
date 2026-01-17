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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Authorisation service interface
 *
 * @since  __DEPLOY_VERSION__
 */
interface AuthServiceInterface
{
	/**
	 * Validate the given token
	 *
	 * @param   string                  $token
	 * @param   ServerRequestInterface  $request
	 *
	 * @return TokenInfo|null The token information. Null if the token is invalid or expired.
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function validateToken(string $token, ServerRequestInterface $request): ?TokenInfo;
}
