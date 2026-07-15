<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

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
class OAuthClientModel extends BaseDatabaseModel
{
    public function store(array $data): void
    {
        $db     = $this->getDatabase();
        $time   = time();
        $object = (object) [
            'pid'           => $data['pid'] ?? 0,
            'tstamp'        => $data['tstamp'] ?? $time,
            'crdate'        => $data['crdate'] ?? $time,
            'client_id'     => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'client_name'   => $data['client_name'],
            'redirect_uris' => $data['redirect_uris'],
            'grant_types'   => $data['grant_types'],
            'scope'         => $data['scope'],
        ];
        if (!$db->insertObject('#__mcp_oauth_clients', $object)) {
            throw new \RuntimeException('Failed to insert OAuth client');
        }
    }
}
