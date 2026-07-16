<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mcp\Tool;

use Joomla\CMS\WebService\Internal\ComponentApiDispatcher;
use Joomla\CMS\WebService\Internal\InternalApiDispatcherInterface;
use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
use Joomla\CMS\WebService\Operation\OperationDefinition;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Executes compiled operations through Joomla's existing component dispatcher without leaving the application.
 *
 * @since  __DEPLOY_VERSION__
 */
final class InternalApiOperationInvoker implements OperationInvokerInterface
{
    public function __construct(
        private readonly OperationArgumentMapper $argumentMapper = new OperationArgumentMapper(),
        private readonly InternalApiDispatcherInterface $dispatcher = new ComponentApiDispatcher(),
    ) {
    }

    public function invoke(OperationDefinition $operation, array $arguments): OperationResult
    {
        $response = $this->dispatcher->dispatch(
            $operation,
            $this->argumentMapper->map($operation, $arguments),
        );

        $isSuccess = $response->statusCode >= 200 && $response->statusCode < 300;

        // Only a successful response carries a JSON:API resource to flatten. An error response carries an error body
        // whose message must survive verbatim, so it is passed through untouched.
        $body = $response->body;

        if ($isSuccess) {
            $body = $this->normaliseResponseBody($body);
            // Joomla emits dates in its stored Y-m-d H:i:s format. The mapper converts a date-time argument to that
            // format on the way in; this converts it back to the RFC 3339 the schema declares on the way out, so the
            // contract is honoured symmetrically on read and write.
            $body = $this->normaliseOutputFormats($body, $operation->outputSchema);
        }

        return new OperationResult($response->statusCode, $body, $response->mediaType);
    }

    /**
     * Recursively converts every value the schema marks as a date-time from Joomla's stored format to RFC 3339.
     *
     * @param array<string, mixed>|null $schema
     */
    private function normaliseOutputFormats(mixed $body, ?array $schema): mixed
    {
        if ($schema === null) {
            return $body;
        }

        if (($schema['type'] ?? null) === 'array' && \is_array($body)) {
            $items = \is_array($schema['items'] ?? null) ? $schema['items'] : null;

            return array_map(fn ($value): mixed => $this->normaliseOutputFormats($value, $items), $body);
        }

        if (!\is_array($body) || !\is_array($schema['properties'] ?? null)) {
            return $body;
        }

        foreach ($schema['properties'] as $name => $propertySchema) {
            if (!\array_key_exists($name, $body) || !\is_array($propertySchema)) {
                continue;
            }

            if (isset($propertySchema['properties']) || ($propertySchema['type'] ?? null) === 'array') {
                $body[$name] = $this->normaliseOutputFormats($body[$name], $propertySchema);
                continue;
            }

            if (($propertySchema['format'] ?? null) === 'date-time') {
                $body[$name] = $this->toRfc3339($body[$name]);
            }
        }

        return $body;
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
            $normalised['id'] = ctype_digit((string) $resource['id'])
                ? (int) $resource['id']
                : $resource['id'];
        }

        return $normalised;
    }
}
