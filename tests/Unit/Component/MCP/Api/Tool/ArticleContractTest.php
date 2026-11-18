<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Tool;

use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResult;
use Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResultMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Checks the article contract against real webservice responses.
 *
 * The fixtures are responses of a Joomla 6.2 site, with the site URL replaced by https://example.org/. The article in
 * the item fixture has two tags, an intro image marked as decorative, metadata and schema.org data. An MCP client may
 * validate the structured content of a tool result against its output schema, so the mapped responses must match it.
 *
 * @covers \Joomla\CMS\WebService\Definition\Resource\Schema\SchemaFactory
 * @covers \Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation\OperationResultMapper
 */
final class ArticleContractTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function responses(): array
    {
        return [
            'list' => ['list', 'article-list.json'],
            'read' => ['get', 'article-item.json'],
        ];
    }

    #[DataProvider('responses')]
    public function testMappedResponseMatchesTheOutputSchema(string $operationName, string $fixture): void
    {
        $operation = $this->operation($operationName);
        $raw       = json_decode(file_get_contents(__DIR__ . '/fixtures/' . $fixture), true, 512, JSON_THROW_ON_ERROR);
        $body      = (new OperationResultMapper())->map($operation, new OperationResult(200, $raw))->body;

        // Compare the JSON a client receives, in which an object and a list are distinct.
        $json = json_decode(json_encode($body, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        self::assertSame([], $this->violations($json, $operation->outputSchema, ''));
    }

    public function testTagsAreReadAsAMapOfIdentifierToTitle(): void
    {
        $raw  = json_decode(file_get_contents(__DIR__ . '/fixtures/article-item.json'), true, 512, JSON_THROW_ON_ERROR);
        $body = (new OperationResultMapper())->map($this->operation('get'), new OperationResult(200, $raw))->body;

        self::assertSame([3 => 'Example tag', 2 => 'Tag probe'], $body['tags']);
    }

    private function operation(string $name): OperationDefinition
    {
        foreach ((new OperationCompiler())->compile(ArticlesController::class) as $operation) {
            if (str_ends_with($operation->operationId, '.' . $name)) {
                return $operation;
            }
        }

        self::fail('No ' . $name . ' operation for articles');
    }

    /**
     * Validates a decoded JSON value against the subset of JSON Schema the schema factory produces.
     *
     * @param array<string, mixed> $schema
     *
     * @return list<string>
     */
    private function violations(mixed $value, array $schema, string $path): array
    {
        if (isset($schema['anyOf'])) {
            foreach ($schema['anyOf'] as $alternative) {
                if ($this->violations($value, $alternative, $path) === []) {
                    return [];
                }
            }

            return [$path . ': matches no alternative of anyOf'];
        }

        if (isset($schema['type']) && !\in_array($this->jsonType($value), (array) $schema['type'], true)) {
            // A JSON integer is also a number.
            if (!(\is_int($value) && \in_array('number', (array) $schema['type'], true))) {
                return [$path . ': is ' . $this->jsonType($value) . ', expected ' . implode('|', (array) $schema['type'])];
            }
        }

        if (isset($schema['enum']) && !\in_array($value, $schema['enum'], true)) {
            return [$path . ': is not one of the enum values'];
        }

        $violations = [];

        if (\is_array($value) && isset($schema['items'])) {
            foreach ($value as $index => $item) {
                array_push($violations, ...$this->violations($item, $schema['items'], $path . '/' . $index));
            }
        }

        if ($value instanceof \stdClass) {
            $properties = $schema['properties'] ?? [];

            foreach ($schema['required'] ?? [] as $name) {
                if (!property_exists($value, $name)) {
                    $violations[] = $path . '/' . $name . ': is required but missing';
                }
            }

            foreach (get_object_vars($value) as $name => $propertyValue) {
                if (isset($properties[$name])) {
                    array_push($violations, ...$this->violations($propertyValue, $properties[$name], $path . '/' . $name));
                } elseif (($schema['additionalProperties'] ?? true) === false) {
                    $violations[] = $path . '/' . $name . ': is not allowed';
                } elseif (\is_array($schema['additionalProperties'] ?? null)) {
                    array_push($violations, ...$this->violations($propertyValue, $schema['additionalProperties'], $path . '/' . $name));
                }
            }
        }

        return $violations;
    }

    private function jsonType(mixed $value): string
    {
        return match (true) {
            $value === null    => 'null',
            \is_bool($value)   => 'boolean',
            \is_int($value)    => 'integer',
            \is_float($value)  => 'number',
            \is_string($value) => 'string',
            \is_array($value)  => 'array',
            default            => 'object',
        };
    }
}
