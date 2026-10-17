<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MCP\Joomla\Tool\WebserviceWrapper\Operation;

use Joomla\CMS\WebService\Operation\OperationDefinition;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Converts a raw JSON:API result of the web service dispatcher into the flat structure an operation's output schema
 * declares. This is the counterpart of the OperationArgumentMapper.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationResultMapper
{
    public function map(OperationDefinition $operation, OperationResult $result): OperationResult
    {
        // Only a successful response carries a JSON:API resource to flatten. An error response carries an error body
        // whose message must survive verbatim, so it is passed through untouched.
        if (!$result->isSuccessful()) {
            return $result;
        }

        // Joomla emits dates in its stored Y-m-d H:i:s format. The argument mapper converts a date-time argument to
        // that format on the way in; this converts it back to the RFC 3339 the schema declares on the way out.
        $body = $this->normaliseOutputFormats(
            $this->normaliseResponseBody($result->body),
            $operation->outputSchema,
        );

        return new OperationResult($result->statusCode, $body, $result->mediaType);
    }

    /**
     * Recursively converts every value the schema marks as a date-time from Joomla's stored format to RFC 3339.
     *
     * @param array<string, mixed>|null $schema
     */
    private function normaliseOutputFormats(mixed $body, ?array $schema, bool $nested = false): mixed
    {
        if ($schema === null) {
            return $body;
        }

        if (($schema['type'] ?? null) === 'array' && \is_array($body)) {
            $items = \is_array($schema['items'] ?? null) ? $schema['items'] : null;

            return array_map(fn ($value): mixed => $this->normaliseOutputFormats($value, $items, $nested), $body);
        }

        if (!\is_array($body) || !\is_array($schema['properties'] ?? null)) {
            return $body;
        }

        foreach ($schema['properties'] as $name => $propertySchema) {
            if (!\array_key_exists($name, $body) || !\is_array($propertySchema)) {
                continue;
            }

            if (
                isset($propertySchema['properties'])
                || $this->isType($propertySchema, 'array')
                || $this->isType($propertySchema, 'object')
            ) {
                $body[$name] = $this->normaliseOutputFormats($body[$name], $propertySchema, true);

                // Joomla encodes an unset object, such as the images of an article, as an empty list. The schema
                // declares an object, and an empty PHP array would be serialised as a JSON list.
                if ($body[$name] === [] && $this->isType($propertySchema, 'object')) {
                    $body[$name] = new \stdClass();
                }

                continue;
            }

            if (($propertySchema['format'] ?? null) === 'date-time') {
                $body[$name] = $this->toRfc3339($body[$name]);
            }
        }

        // Joomla omits unset fields of a stored object. The schema requires them, so they take the declared default.
        // The resource itself is left as Joomla reports it.
        foreach ($nested ? ($schema['required'] ?? []) : [] as $name) {
            if (!\array_key_exists($name, $body) && \array_key_exists('default', $schema['properties'][$name] ?? [])) {
                $body[$name] = $schema['properties'][$name]['default'];
            }
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function isType(array $schema, string $type): bool
    {
        return \in_array($type, (array) ($schema['type'] ?? []), true);
    }

    /**
     * Formats a stored Joomla date-time as RFC 3339 in UTC. A null, empty or zero-date sentinel becomes null; a value
     * that cannot be parsed is left untouched rather than discarded.
     */
    private function toRfc3339(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            return $value;
        }

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))
                ->format(\DateTimeInterface::RFC3339);
        } catch (\Exception) {
            return $value;
        }
    }

    private function normaliseResponseBody(mixed $body): mixed
    {
        if (!\is_array($body) || !\array_key_exists('data', $body)) {
            return $body;
        }

        return $this->normaliseJsonApiData($body['data']);
    }

    private function normaliseJsonApiData(mixed $data): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        if (array_is_list($data)) {
            return array_map($this->normaliseJsonApiResource(...), $data);
        }

        return $this->normaliseJsonApiResource($data);
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return array<string, mixed>
     */
    private function normaliseJsonApiResource(array $resource): array
    {
        $normalised = \is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];

        if (\array_key_exists('id', $resource) && !\array_key_exists('id', $normalised)) {
            $normalised['id'] = $this->identifier($resource['id']);
        }

        // A relationship such as the category of an article is not an attribute. Unless an attribute of the same name
        // exists, it is reported as its resource identifier, the way the output schema declares it.
        foreach (\is_array($resource['relationships'] ?? null) ? $resource['relationships'] : [] as $name => $relationship) {
            if (\array_key_exists($name, $normalised) || !\is_array($relationship) || !\array_key_exists('data', $relationship)) {
                continue;
            }

            $data = $relationship['data'];

            $normalised[$name] = match (true) {
                !\is_array($data)    => $data,
                array_is_list($data) => array_map($this->resourceIdentifier(...), $data),
                default              => $this->resourceIdentifier($data),
            };
        }

        return $normalised;
    }

    /**
     * @param array<string, mixed> $identifier
     *
     * @return array<string, mixed>
     */
    private function resourceIdentifier(array $identifier): array
    {
        if (\array_key_exists('id', $identifier)) {
            $identifier['id'] = $this->identifier($identifier['id']);
        }

        return $identifier;
    }

    private function identifier(mixed $id): mixed
    {
        return ctype_digit((string) $id) ? (int) $id : $id;
    }
}
