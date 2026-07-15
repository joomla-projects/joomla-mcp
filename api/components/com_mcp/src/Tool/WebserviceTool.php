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
use Mcp\Types\CallToolResult;
use Mcp\Types\TextContent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Generic MCP tool backed by a canonical Joomla web service operation.
 *
 * @since  __DEPLOY_VERSION__
 */
final class WebserviceTool implements ToolInterface
{
    public function __construct(
        private readonly OperationDefinition $operation,
        private readonly OperationInvokerInterface $invoker,
    ) {
    }

    public function getName(): string
    {
        return $this->operation->operationId;
    }

    public function getSchema(): array
    {
        $schema = [
            'title'       => $this->operation->title,
            'description' => $this->operation->description,
            'inputSchema' => $this->operation->inputSchema,
            'annotations' => $this->operation->annotations,
        ];

        if ($this->operation->outputSchema !== null) {
            $schema['outputSchema'] = $this->operation->outputSchema;
        }

        return $schema;
    }

    public function execute(array $params): CallToolResult
    {
        try {
            $result = $this->invoker->invoke($this->operation, $params);
            $text   = $this->formatBody($result->body, $result->statusCode);

            return new CallToolResult(
                [new TextContent($text)],
                !$result->isSuccessful(),
                null,
                $result->isSuccessful() && \is_array($result->body) ? $result->body : null,
            );
        } catch (\Throwable $exception) {
            return new CallToolResult(
                [
                    new TextContent(
                        \sprintf(
                            '%s could not be executed: %s',
                            $this->operation->operationId,
                            $exception->getMessage(),
                        ),
                    ),
                ],
                true,
            );
        }
    }

    private function formatBody(mixed $body, int $statusCode): string
    {
        if ($body === null || $body === '') {
            return \sprintf('%s completed with HTTP status %d.', $this->operation->operationId, $statusCode);
        }

        if (\is_string($body)) {
            return $body;
        }

        $encoded = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false
            ? \sprintf('%s returned an unserialisable response.', $this->operation->operationId)
            : $encoded;
    }
}
