<?php

namespace Joomla\Plugin\Mcp\Joomla\Tool;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;

class PurgeCache implements ToolInterface
{

    public function getName(): string
    {
        return "purgeCache";
    }

    public function getSchema(): array
    {
        return [
            "inputSchema" => [
                "type" => "object"
            ]
        ];
    }

    public function execute(array $params): CallToolResult
    {
        $cache = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory();
        /** @var Joomla\Component\Cache\Administrator\Model\CacheModel $model */
        $model = $cache->createModel('Cache', 'Administrator', ['ignore_request' => true]);

        $mCache = $model->getCache();

        foreach ($mCache->getAll() as $cache) {
            if (!$mCache->clean($cache->group)) {
                return new CallToolResult(
                    [
                        new TextContent(Text::sprintf('PLG_MCP_JOOMLA_PURGECACHE_ERROR', $cache->group))
                    ],
                    true
                );
            }
        }

        return new CallToolResult(
            [
                new TextContent(Text::_('PLG_MCP_JOOMLA_PURGECACHE_SUCCESS'))
            ]
        );
    }
}
