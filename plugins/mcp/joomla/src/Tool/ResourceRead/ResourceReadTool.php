<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Mcp.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MCP\Joomla\Tool\ResourceRead;

use Joomla\CMS\MCP\Resource\ResourceInterface;
use Joomla\CMS\MCP\Tool\ToolInterface;
use Joomla\CMS\MCP\Tool\ToolResult;

/**
 * Exposes a resource as a read-only tool.
 *
 * Many MCP clients only let the model call tools and never read resources on their own,
 * so information that is only available as a resource stays invisible to the model.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceReadTool implements ToolInterface
{
    /**
     * @param   ResourceInterface  $resource     The resource to read
     * @param   string             $name         The tool name
     * @param   string             $description  The tool description shown to the model
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly string $name,
        private readonly string $description,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): array
    {
        return [
            'title'       => $this->resource->getTitle(),
            'description' => $this->description,
            'inputSchema' => [
                'type' => 'object',
            ],
            'annotations' => [
                'readOnlyHint'    => true,
                'destructiveHint' => false,
                'idempotentHint'  => true,
                'openWorldHint'   => false,
            ],
        ];
    }

    public function execute(array $params): ToolResult
    {
        $text = [];

        foreach ($this->resource->read()->getContents() as $contents) {
            if ($contents->text !== null) {
                $text[] = $contents->text;
            }
        }

        return ToolResult::text(implode("\n", $text));
    }
}
