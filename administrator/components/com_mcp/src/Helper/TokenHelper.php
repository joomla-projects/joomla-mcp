<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;

/**
 * Client token helper.
 *
 * Uses the same scheme as the core Joomla API Token: only a random seed is stored in
 * #__mcp.client_token, the token handed to the client is
 * base64_encode("algo:id:" . hash_hmac(algo, base64_decode(seed), site secret)).
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class TokenHelper
{
    /**
     * Allowed HMAC algorithms.
     *
     * @var    string[]
     * @since  __DEPLOY_VERSION__
     */
    private const ALLOWED_ALGOS = ['sha256', 'sha512'];

    /**
     * Generate a new random token seed to store in the database.
     *
     * @return  string  Base64 encoded 32 random bytes
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function generateSeed(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Build the client token from the record id and its stored seed.
     *
     * @param   int     $id    The #__mcp record id
     * @param   string  $seed  The stored seed
     * @param   string  $algo  The HMAC algorithm
     *
     * @return  string  The token to send as Bearer token
     *
     * @throws  \RuntimeException  When the site secret is empty
     * @since   __DEPLOY_VERSION__
     */
    public static function buildToken(int $id, string $seed, string $algo = 'sha256'): string
    {
        return base64_encode($algo . ':' . $id . ':' . self::hmac($seed, $algo));
    }

    /**
     * Split a client token into its algorithm, record id and HMAC.
     *
     * @param   string  $token  The token sent by the client
     *
     * @return  array|null  ['algo' => string, 'id' => int, 'hmac' => string] or null when malformed
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function parseToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);

        if ($decoded === false) {
            return null;
        }

        $parts = explode(':', $decoded, 3);

        if (\count($parts) !== 3) {
            return null;
        }

        [$algo, $id, $hmac] = $parts;

        if (!\in_array($algo, self::ALLOWED_ALGOS, true) || !ctype_digit($id) || (int) $id <= 0 || $hmac === '') {
            return null;
        }

        return ['algo' => $algo, 'id' => (int) $id, 'hmac' => $hmac];
    }

    /**
     * Check a presented HMAC against the one derived from the stored seed.
     *
     * @param   string  $hmac  The HMAC part of the presented token
     * @param   string  $seed  The stored seed
     * @param   string  $algo  The HMAC algorithm
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function verify(string $hmac, string $seed, string $algo): bool
    {
        if ($seed === '') {
            return false;
        }

        try {
            return hash_equals(self::hmac($seed, $algo), $hmac);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Compute the HMAC of a seed with the site secret.
     *
     * @param   string  $seed  The stored seed
     * @param   string  $algo  The HMAC algorithm
     *
     * @return  string
     *
     * @throws  \RuntimeException  When the site secret is empty
     * @since   __DEPLOY_VERSION__
     */
    private static function hmac(string $seed, string $algo): string
    {
        $secret = (string) Factory::getApplication()->get('secret');

        if ($secret === '') {
            throw new \RuntimeException('The site secret is empty; cannot derive MCP client tokens.');
        }

        return hash_hmac($algo, base64_decode($seed), $secret);
    }
}
