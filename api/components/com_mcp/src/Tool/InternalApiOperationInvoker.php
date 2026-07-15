<?php

/**
 * @package     Joomla.API
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\MCP\Api\Tool;

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
        private readonly OperationArgumentMapper        $argumentMapper,
        private readonly InternalApiDispatcherInterface $dispatcher,
    )
    {
    }

    public function invoke(OperationDefinition $operation, array $arguments): OperationResult
    {
        $response = $this->dispatcher->dispatch(
            $operation,
            $this->argumentMapper->map($operation, $arguments),
        );

        return new OperationResult(
            $response->statusCode,
            $this->normaliseResponseBody($response->body),
            $response->mediaType,
        );
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
     * @param array<string, mixed> $resource JSON:API resource object.
     *
     * @return  array<string, mixed>
     */
    private function normaliseJsonApiResource(array $resource): array
    {
        $normalised = \is_array($resource['attributes'] ?? null) ? $resource['attributes'] : [];

        if (\array_key_exists('id', $resource) && !\array_key_exists('id', $normalised)) {
            $normalised['id'] = ctype_digit((string)$resource['id']) ? (int)$resource['id'] : $resource['id'];
        }

        return $normalised;
    }
}
