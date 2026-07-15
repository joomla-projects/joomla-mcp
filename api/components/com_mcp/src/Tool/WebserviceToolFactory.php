<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationDefinition;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Creates generic MCP tools from canonical web service operations.
 *
 * Discovery and exposure filtering happen before this factory is called. The factory is deliberately limited to the
 * MCP projection and does not know about controllers, routes or operation compilation.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class WebserviceToolFactory
{
    public function __construct(private OperationInvokerInterface $invoker)
    {
    }

    public function create(OperationDefinition $operation): WebserviceTool
    {
        return new WebserviceTool($operation, $this->invoker);
    }
}
