<?php

/**
 * @package         Joomla.API
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Exception;

class NoWebApplicationException extends \RuntimeException
{
    public function __construct()
    {
        $message = 'Cannot respond to HTTP request without a web application.';
        $code    = 500;

        parent::__construct($message, $code);
    }
}
