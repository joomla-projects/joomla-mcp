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
 * Removes secrets and credentials from tool call arguments before they are written to a log.
 *
 * - Known secrets (the client token, the site secret, the passwords from the Global Configuration) are replaced with
 *   "***" wherever they appear, also inside longer strings and in array keys.
 * - Values of keys that look like credentials ("password", "api_key", "token", ...) are masked completely.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ArgumentRedactor
{
    /**
     * Values shorter than this are not treated as secrets, so that redaction doesn't mangle common words and numbers
     *
     * @since  __DEPLOY_VERSION__
     */
    public const MIN_LENGTH = 8;

    /**
     * The replacement for redacted values
     *
     * @since  __DEPLOY_VERSION__
     */
    private const MASK = '***';

    /**
     * Maximum nesting depth that is redacted; anything deeper is masked as a whole
     *
     * @since  __DEPLOY_VERSION__
     */
    private const MAX_DEPTH = 64;

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
     * Keys whose values are always masked. Deliberately narrow, so that ordinary fields like "metakey" or "author"
     * stay readable.
     *
     * @since  __DEPLOY_VERSION__
     */
    private const CREDENTIAL_KEY_PATTERN = '/password|passwd|pass$|secret|token|api_?key|private_?key|credential|(^|_)auth$/i';

    /**
     * @var string[]  The known secrets
     *
     * @since  __DEPLOY_VERSION__
     */
    private array $secrets = [];

    /**
     * Constructor.
     *
     * @param  array  $secrets  The known secrets. Non-strings, and strings shorter than MIN_LENGTH after trimming,
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
     * Create a redactor for the client token and the secrets from the Global Configuration
     *
     * @param  object       $config  An object with a get() method, e.g. the application
     * @param  string|null  $token   The client token of the current request
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function fromConfiguration(object $config, ?string $token): self
    {
        $secrets = [$token];

        // The decoded form of the token holds the HMAC that proves possession, so it is a secret as well
        if ($token !== null && ($decoded = base64_decode($token, true)) !== false) {
            $secrets[] = $decoded;
        }

        foreach (self::CONFIGURATION_KEYS as $key) {
            $secrets[] = $config->get($key);
        }

        return new self($secrets);
    }

    /**
     * Return a copy of the value with secrets and credentials replaced by "***"
     *
     * Objects are converted to arrays of their public properties.
     *
     * @param  mixed  $value  The value to redact, usually the tool call arguments
     *
     * @return  mixed
     *
     * @since   __DEPLOY_VERSION__
     */
    public function redact(mixed $value): mixed
    {
        return $this->redactValue($value, 0);
    }

    /**
     * Redact a value at the given nesting depth
     *
     * @param  mixed    $value  The value
     * @param  integer  $depth  The current depth
     *
     * @return  mixed
     *
     * @since   __DEPLOY_VERSION__
     */
    private function redactValue(mixed $value, int $depth): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            return self::MASK;
        }

        if (\is_string($value)) {
            return $this->secrets === [] ? $value : str_replace($this->secrets, self::MASK, $value);
        }

        if (\is_object($value)) {
            $value = get_object_vars($value);
        }

        if (!\is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $key => $item) {
            $safeKey = \is_string($key) ? $this->redactValue($key, $depth + 1) : $key;

            $redacted[$safeKey] = \is_string($key) && preg_match(self::CREDENTIAL_KEY_PATTERN, $key)
                ? self::MASK
                : $this->redactValue($item, $depth + 1);
        }

        return $redacted;
    }
}
