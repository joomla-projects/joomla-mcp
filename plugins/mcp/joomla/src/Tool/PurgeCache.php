<?php

namespace Joomla\Plugin\Mcp\Joomla\Tool;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mcp\Tool\ToolInterface;
use Joomla\CMS\Mcp\Tool\ToolResult;

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
                "type" => "object",
            ],
        ];
    }

    public function execute(array $params): ToolResult
    {
        $cache = Factory::getApplication()->bootComponent('com_cache')->getMVCFactory();
        /** @var Joomla\Component\Cache\Administrator\Model\CacheModel $model */
        $model = $cache->createModel('Cache', 'Administrator', ['ignore_request' => true]);

        $mCache = $model->getCache();

        foreach ($mCache->getAll() as $cache) {
            if (!$mCache->clean($cache->group)) {
                return ToolResult::error(Text::sprintf('PLG_MCP_JOOMLA_PURGE_CACHE_ERROR', $cache->group));
            }
        }

        return ToolResult::text(Text::_('PLG_MCP_JOOMLA_PURGE_CACHE_SUCCESS'));
    }
}
