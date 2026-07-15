<?php

/**
 * @package         Joomla.Administrator
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Event;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Prompt\PromptInterface;
use Joomla\Component\MCP\Api\Resource\ResourceInterface;
use Joomla\Component\MCP\Api\Resource\ResourceTemplateInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use Joomla\Event\Event;

class InitialiseMCPServerEvent extends Event
{
    public function __construct(AbilityRegistry $abilities)
    {
        $arguments['abilities'] = $abilities;

        parent::__construct('initialiseMCPServerEvent', $arguments);
    }

    public function addAbility(ResourceInterface|ResourceTemplateInterface|ToolInterface|PromptInterface $ability): void
    {
        $this->arguments['abilities']->addAbility($ability);
    }
}
