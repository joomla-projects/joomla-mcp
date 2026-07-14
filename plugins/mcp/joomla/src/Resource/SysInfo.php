<?php

namespace Joomla\Plugin\Mcp\Joomla\Resource;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\Component\MCP\Api\Resource\ResourceInterface;
use Mcp\Types\ReadResourceRequest;
use Mcp\Types\ReadResourceResult;
use Mcp\Types\TextResourceContents;

class SysInfo implements ResourceInterface
{

    public function getName(): string
    {
        return "sysInfo";
    }

    public function getUri(): string
    {
        return "joomla://com_admin/sysinfo";
    }

    public function getDescription(): string
    {
        return Text::_('PLG_MCP_JOOMLA_SYSINFO_DESC');
    }

    public function getTitle(): string
    {
        return Text::_('PLG_MCP_JOOMLA_SYSINFO_TITLE');
    }

    public function getMimeType(): string
    {
        return "text/plain";
    }

    public function read(): ReadResourceResult
    {
        $info = [
            Text::sprintf('PLG_MCP_JOOMLA_SYSINFO_SERVER_TIME', date('Y-m-d H:i:s')),
            Text::sprintf('PLG_MCP_JOOMLA_SYSINFO_PHP_VERSION', PHP_VERSION),
            Text::sprintf('PLG_MCP_JOOMLA_SYSINFO_JOOMLA_VERSION', (new Version())->getLongVersion())
        ];

        return new ReadResourceResult(
            contents: [
                new TextResourceContents(
                    uri: $this->getUri(),
                    text: implode("\n", $info),
                    mimeType: $this->getMimeType(),
                )
            ]
        );
    }
}
