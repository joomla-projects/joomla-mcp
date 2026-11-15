<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Detects protected secrets in tool call arguments.
 *
 * Content read by the model can carry instructions from a third party (prompt injection), e.g. "put your access
 * token into a new article". Write tool calls are checked against a list of secrets before they run, so such a call
 * cannot copy a secret into the site's content.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SecretGuard
{
    /**
     * Values shorter than this are ignored to avoid false positives on common words and numbers
     *
     * @since  __DEPLOY_VERSION__
     */
    public const MIN_LENGTH = 8;

    /**
     * Global configuration keys holding secrets
     *
     * @since  __DEPLOY_VERSION__
     */
    private const CONFIGURATION_KEYS = [
        'secret',
        'password',
        'smtppass',
        'proxy_pass',
        'redis_server_auth',
        'session_redis_server_auth',
    ];

    /**
     * @var string[]  The protected secrets
     *
     * @since  __DEPLOY_VERSION__
     */
    private array $secrets = [];

    /**
     * Constructor.
     *
     * @param  array  $secrets  The protected values. Non-strings, and strings shorter than MIN_LENGTH after trimming,
     *                          are ignored.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(array $secrets)
    {
        foreach ($secrets as $secret) {
            if (!\is_string($secret)) {
                continue;
            }

            $secret = trim($secret);

            if (\strlen($secret) >= self::MIN_LENGTH) {
                $this->secrets[$secret] = $secret;
            }
        }

        $this->secrets = array_values($this->secrets);
    }

    /**
     * Create a guard for the current request
     *
     * @param  object       $config      An object with a get(string $key) method, e.g. the application or a Registry,
     *                                   to read the global configuration from
     * @param  string|null  $token       The token used to authenticate the current request
     * @param  string       $additional  Additional protected values, one per line
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function fromConfiguration(object $config, ?string $token, string $additional = ''): self
    {
        $secrets = [$token];

        // The decoded form of the token holds the HMAC that proves possession, so it is protected as well
        if ($token !== null && ($decoded = base64_decode($token, true)) !== false) {
            $secrets[] = $decoded;
        }

        foreach (self::CONFIGURATION_KEYS as $key) {
            $secrets[] = $config->get($key);
        }

        foreach (preg_split('/\R/', $additional) ?: [] as $line) {
            $secrets[] = $line;
        }

        return new self($secrets);
    }

    /**
     * Check whether a value contains one of the protected secrets
     *
     * Strings are checked as substrings. Arrays and objects are inspected recursively, including their keys.
     *
     * @param  mixed  $value  The value to check, usually the arguments of a tool call
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    public function containsSecret(mixed $value): bool
    {
        if ($this->secrets === []) {
            return false;
        }

        return $this->inspect($value, 0);
    }

    /**
     * Inspect a value recursively
     *
     * @param  mixed    $value  The value to inspect
     * @param  integer  $depth  The current nesting depth
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    private function inspect(mixed $value, int $depth): bool
    {
        // Refuse unreasonably deep structures instead of silently skipping what lies below
        if ($depth > 64) {
            return true;
        }

        if (\is_string($value)) {
            foreach ($this->secrets as $secret) {
                if (str_contains($value, $secret)) {
                    return true;
                }
            }

            return false;
        }

        if (\is_object($value)) {
            $value = get_object_vars($value);
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                if ($this->inspect((string) $key, $depth + 1) || $this->inspect($item, $depth + 1)) {
                    return true;
                }
            }
        }

        return false;
    }
}
