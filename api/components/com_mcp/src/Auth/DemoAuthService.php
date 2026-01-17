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
class DemoAuthService implements AuthServiceInterface
{
	public function validateToken(string $token, ServerRequestInterface $request): ?TokenInfo
	{
		return new TokenInfo((int) $token);
	}
}
