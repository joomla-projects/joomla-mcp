<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Event;

use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Prompt\PromptInterface;
use Joomla\Component\MCP\Api\Resource\ResourceInterface;
use Joomla\Component\MCP\Api\Resource\ResourceTemplateInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use Joomla\Event\Event;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Event used by MCP plugins to register tools, resources and prompts.
 *
 * @since  __DEPLOY_VERSION__
 */
final class RegisterMcpAbilitiesEvent extends Event
{
    public const NAME = 'onRegisterMcpAbilities';

    public function __construct(AbilityRegistry $abilities)
    {
        parent::__construct(self::NAME, ['abilities' => $abilities]);
    }

    public function addAbility(
        PromptInterface|ResourceInterface|ResourceTemplateInterface|ToolInterface $ability,
    ): void {
        $this->arguments['abilities']->addAbility($ability);
    }
}
