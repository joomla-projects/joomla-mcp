<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Mcp\Server\Transport\Http\HttpMessage;

/**
 * Extracts an OAuth Bearer token from the HTTP Authorization header only.
 *
 * @since  __DEPLOY_VERSION__
 */
final class BearerTokenExtractor
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function extract(HttpMessage $request): ?string
    {
        $header = $request->getHeader('Authorization') ?? '';

        if ($header === '') {
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }

        if (
            $header === ''
            && \PHP_SAPI === 'apache2handler'
            && \function_exists('apache_request_headers')
        ) {
            $headers = apache_request_headers();

            if (\is_array($headers)) {
                $headers = array_change_key_case($headers, CASE_LOWER);
                $header  = (string) ($headers['authorization'] ?? '');
            }
        }

        if ($header === '') {
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        if (preg_match('/^Bearer[ \t]+([^\s,]+)$/i', trim($header), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
