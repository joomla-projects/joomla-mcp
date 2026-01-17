<?php declare(strict_types=1);
/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * MCP Model
 *
 * @since  __DEPLOY_VERSION__
 */
class AccessTokenModel extends BaseDatabaseModel
{
	/**
	 * Override the legacy error handling behaviour.
	 *
	 * @var bool
	 * @since __DEPLOY_VERSION__
	 *        To be removed in Joomla 7.0
	 */
	protected $useExceptions = true;

	/**
	 * Store an access token in the database
	 *
	 * @param   array  $data
	 *
	 * @return void
	 * @since __DEPLOY_VERSION__
	 */
	public function storeAccessToken(array $data): void
	{
		$db = $this->getDatabase();
		$time   = time();
		$object = (object) [
			'pid'          => $data['pid'] ?? 0,
			'tstamp'       => $data['tstamp'] ?? $time,
			'crdate'       => $data['crdate'] ?? $time,
			'token'        => $data['token'],
			'userid'       => $data['userid'],
			'client_name'  => $data['client_name'],
			'expires'      => $data['expires'],
			'last_used'    => $data['last_used'],
			'created_ip'   => $data['created_ip'],
			'last_used_ip' => $data['last_used_ip'],
		];
		if (!$db->insertObject('#__mcp_access_tokens', $object)) {
			throw new \RuntimeException('Failed to insert access token');
		}
	}
}
