<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Administrator\Event;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Mcp\Prompt\PromptInterface;
use Joomla\CMS\Mcp\Resource\ResourceInterface;
use Joomla\CMS\Mcp\Resource\ResourceTemplateInterface;
use Joomla\CMS\Mcp\Tool\ToolInterface;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
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
