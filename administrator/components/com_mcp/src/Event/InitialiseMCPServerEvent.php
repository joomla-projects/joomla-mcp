<?php declare(strict_types=1);
/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Administrator\Event;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Event\Event;
use Mcp\Server\Server;

class InitialiseMCPServerEvent extends Event
{
    public function __construct(Server $server, $options = [])
    {
        $options['server'] = $server;

        parent::__construct('initialiseMCPServerEvent', $options);
    }
}
