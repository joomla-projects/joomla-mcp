<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

/**
 * Canonical transport-neutral definition of a web service operation.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class OperationDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param array<string, mixed>|null $outputSchema
     * @param array<string, mixed> $requestBodySchema
     * @param array<string, array{argument: string, schema: array<string, mixed>}> $queryParameters
     * @param array<string, array{argument: string, schema: array<string, mixed>}> $pathParameters
     * @param array<string, mixed> $acl
     * @param array<string, bool> $annotations
     * @param list<string> $tags
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public string $operationId,
        public string $method,
        public string $path,
        public string $controller,
        public string $task,
        public string $title,
        public string $description,
        public array $inputSchema,
        public ?array $outputSchema,
        public array $requestBodySchema = [],
        public array $queryParameters = [],
        public array $pathParameters = [],
        public array $acl = [],
        public array $annotations = [],
        public bool $exposeToMcp = true,
        public bool $public = false,
        public int $successStatus = 200,
        public array $tags = [],
    ) {
    }
}
