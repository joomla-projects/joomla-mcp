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
class OAuthCodeModel extends BaseDatabaseModel
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
	 * Insert an OAuth code into the database
	 *
	 * @param   array  $data  The data to insert
	 *
	 * @return void
	 * @since __DEPLOY_VERSION__
	 */
	public function insertOAuthCode(array $data)
	{
		$db     = $this->getDatabase();
		$time   = time();
		$object = (object) [
			'pid'                   => $data['pid'] ?? 0,
			'tstamp'                => $data['tstamp'] ?? $time,
			'crdate'                => $data['crdate'] ?? $time,
			'code'                  => $data['code'] ?? '',
			'userid'                => $data['userid'] ?? '',
			'client_name'           => $data['client_name'] ?? '',
			'pkce_challenge'        => $data['pkce_challenge'] ?? '',
			'pkce_challenge_method' => $data['pkce_challenge_method'] ?? '',
			'redirect_uri'          => $data['redirect_uri'] ?? '',
			'expires'               => $data['expires'] ?? '',
		];
		if (!$db->insertObject('#__mcp_oauth_codes', $object)) {
			throw new \RuntimeException('Failed to insert OAuth code');
		}
	}

	/**
	 * Get data for an OAuth code from the database
	 *
	 * @param   string  $code     The authorisation code
	 * @param   int     $time     The time to check against
	 * @param   bool    $deleted  Whether to include deleted codes, defaults to false
	 *
	 * @return array  The data associated with the code
	 * @since __DEPLOY_VERSION__
	 */
	public function getOAuthCodeData(string $code, int $time, bool $deleted = false): array
	{
		$db = $this->getDatabase();
		$query = $db->createQuery();
		$query->select('*')
			->from('#__mcp_oauth_codes')
			->where('code = ' . $db->quote($code))
			->where('tstamp >= ' . $time)
			->where('deleted = ' . (int) $deleted);

		return $db->setQuery($query)->loadAssoc();
	}

	/**
	 * Remove an auth code from the database
	 *
	 * @param   int  $uid
	 *
	 * @return void
	 * @since version
	 */
	public function removeAuthCode(int $uid): void
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);
		$query->update('#__mcp_oauth_codes')
			->set('deleted = 1')
			->where('uid = ' . $uid);
		$db->setQuery($query)->execute();
	}
}
