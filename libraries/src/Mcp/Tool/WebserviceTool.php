<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Tool;

use Joomla\CMS\WebService\Operation\OperationDefinition;

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
            $schema['outputSchema'] = $this->isCollection()
                ? $this->collectionSchema($this->operation->outputSchema)
                : $this->operation->outputSchema;
        }

        return $schema;
    }

    public function execute(array $params): ToolResult
    {
        try {
            $result     = $this->invoker->invoke($this->operation, $params);
            $structured = $this->structuredContent($result);
            $text       = $this->formatBody($structured ?? $result->body, $result->statusCode);

            if (!$result->isSuccessful()) {
                return ToolResult::error($text);
            }

            return $structured !== null
                ? ToolResult::structured($structured, $text)
                : ToolResult::text($text);
        } catch (\Throwable $exception) {
            return ToolResult::error(
                \sprintf(
                    '%s could not be executed: %s',
                    $this->operation->operationId,
                    $exception->getMessage(),
                ),
            );
        }
    }

    /**
     * Reports whether the operation yields a collection rather than a single resource.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function isCollection(): bool
    {
        return ($this->operation->outputSchema['type'] ?? null) === 'array';
    }

    /**
     * Wraps a collection schema in the object MCP requires at the top level of an output schema.
     *
     * The operation keeps describing a list as an array, which is what the REST response and the OpenAPI document
     * state. Only the MCP projection needs the object, so the rows are reported under `items`.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function collectionSchema(array $schema): array
    {
        return [
            'type'       => 'object',
            'properties' => ['items' => $schema],
            'required'   => ['items'],
        ];
    }

    /**
     * Builds the structured result, which must match the schema reported by getSchema().
     *
     * @return array<string, mixed>|null
     *
     * @since  __DEPLOY_VERSION__
     */
    private function structuredContent(OperationResult $result): ?array
    {
        if (!$result->isSuccessful() || !\is_array($result->body)) {
            return null;
        }

        return $this->isCollection() ? ['items' => $result->body] : $result->body;
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
