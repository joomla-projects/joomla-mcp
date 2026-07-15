<?php

/**
 * @package     Joomla.MCP
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

/**
 * Holds request-scoped MCP authentication data required by generic operation adapters.
 *
 * The context is populated immediately before the MCP SDK handles a request and cleared in a finally block. It is a
 * transitional bridge until the tool interface accepts an explicit request context.
 *
 * @since  __DEPLOY_VERSION__
 */
final class McpRequestContext
{
    private static ?string $token = null;

    public static function setToken(string $token): void
    {
        self::$token = $token;
    }

    public static function getToken(): ?string
    {
        return self::$token;
    }

    public static function clear(): void
    {
        self::$token = null;
    }

    private function __construct()
    {
    }
}
