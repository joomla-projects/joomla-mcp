<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  MCP.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Plugin\Mcp\Joomla\Tool;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\MCP\Api\Core\McpRequestContext;
use Joomla\Component\MCP\Api\Tool\ScopedAbilityInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;

/**
 * Purges Joomla cache groups.
 *
 * @since  __DEPLOY_VERSION__
 */
final class PurgeCache implements ToolInterface, ScopedAbilityInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'purge_cache';
    }

    /**
     * @inheritDoc
     */
    public function getSchema(): array
    {
        return [
            'title'       => 'Purge Joomla cache',
            'description' => 'Purges all Joomla cache groups available to the represented user.',
            'inputSchema' => [
                'type'                 => 'object',
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'    => false,
                'destructiveHint' => true,
                'idempotentHint'  => true,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function execute(array $params, McpRequestContext $context): CallToolResult
    {
        if (!$context->user->authorise('core.manage', 'com_cache')) {
            return new CallToolResult(
                [new TextContent('The represented Joomla user is not permitted to manage the cache.')],
                true,
            );
        }

        $cacheFactory = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory();
        /** @var \Joomla\Component\Cache\Administrator\Model\CacheModel $model */
        $model  = $cacheFactory->createModel('Cache', 'Administrator', ['ignore_request' => true]);
        $mCache = $model->getCache();

        foreach ($mCache->getAll() as $cache) {
            if (!$mCache->clean($cache->group)) {
                return new CallToolResult(
                    [new TextContent(Text::sprintf('PLG_MCP_JOOMLA_PURGE_CACHE_ERROR', $cache->group))],
                    true,
                );
            }
        }

        return new CallToolResult(
            [new TextContent(Text::_('PLG_MCP_JOOMLA_PURGE_CACHE_SUCCESS'))],
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredScopes(): array
    {
        return ['mcp:administration'];
    }
}
