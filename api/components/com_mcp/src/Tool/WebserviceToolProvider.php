<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Creates and registers MCP tools compiled from attributed Joomla API controllers.
 *
 * @since  __DEPLOY_VERSION__
 */
final class WebserviceToolProvider
{
    public function __construct(
        private readonly OperationCompiler $compiler,
        private readonly OperationInvokerInterface $invoker,
    ) {
    }

    /**
     * @param class-string $controllerClass
     *
     * @return list<WebserviceTool>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getTools(string $controllerClass): array
    {
        $tools = [];

        foreach ($this->compiler->compile($controllerClass) as $operation) {
            if (!$operation->exposeToMcp) {
                continue;
            }

            $tools[] = new WebserviceTool($operation, $this->invoker);
        }

        return $tools;
    }

    /**
     * @param class-string $controllerClass
     *
     * @since  __DEPLOY_VERSION__
     */
    public function register(AbilityRegistry $registry, string $controllerClass): void
    {
        foreach ($this->getTools($controllerClass) as $tool) {
            $registry->addAbility($tool);
        }
    }
}
