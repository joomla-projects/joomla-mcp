<?php

declare(strict_types=1);

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

class TokenInfo
{
    public int $pid;
    public \DateTime $tstamp;
    public \DateTime $crdate;
    public string $token;
    public int $userid;
    public string $client_name;
    public \DateTime $expires;
    public \DateTime $last_used;
    public string $created_ip;
    public string $last_used_ip;

    /**
     * Create a token info object from a validated OAuth2 access token
     *
     * @param int    $userid     Joomla user ID the token was issued to
     * @param string $clientName Name of the OAuth2 client the token was issued to
     * @return TokenInfo
     */
    public static function fromOAuth2(int $userid, string $clientName = ''): self
    {
        $tokenInfo               = new self();
        $tokenInfo->pid          = 0;
        $tokenInfo->tstamp       = new \DateTime('now');
        $tokenInfo->crdate       = new \DateTime('now');
        $tokenInfo->token        = '';
        $tokenInfo->userid       = $userid;
        $tokenInfo->client_name  = $clientName;
        $tokenInfo->expires      = new \DateTime('now');
        $tokenInfo->last_used    = new \DateTime('now');
        $tokenInfo->created_ip   = '';
        $tokenInfo->last_used_ip = '';
        return $tokenInfo;
    }

    /**
     * Create a token info object from an array
     *
     * @param array $data
     * @return TokenInfo
     * @throws \DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        $tokenInfo               = new self();
        $tokenInfo->pid          = $data['pid'] ?? 0;
        $tokenInfo->tstamp       = new \DateTime($data['tstamp'] ?? 'now');
        $tokenInfo->crdate       = new \DateTime($data['crdate'] ?? 'now');
        $tokenInfo->token        = $data['client_token'] ?? '';
        $tokenInfo->userid       = $data['user_id'] ?? 0;
        $tokenInfo->client_name  = $data['client_name'] ?? '';
        $tokenInfo->expires      = new \DateTime($data['expires'] ?? '+ 24 hours');
        $tokenInfo->last_used    = new \DateTime($data['last_used'] ?? 'now');
        $tokenInfo->created_ip   = $data['created_ip'] ?? '';
        $tokenInfo->last_used_ip = $data['last_used_ip'] ?? '';
        return $tokenInfo;
    }
}
