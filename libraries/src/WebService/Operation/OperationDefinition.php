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
     * @param  array<string, mixed>                                                       $inputSchema
     * @param  array<string, mixed>|null                                                  $outputSchema
     * @param  array<string, mixed>                                                       $requestBodySchema
     * @param  array<string, array{argument: string, schema: array<string, mixed>}>        $queryParameters
     * @param  array<string, array{argument: string, schema: array<string, mixed>}>        $pathParameters
     * @param  array<string, mixed>                                                       $acl
     * @param  array<string, bool>                                                        $annotations
     * @param  list<string>                                                               $tags
     * @param  array<string, mixed>                                                       $routeDefaults
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
        public array $routeDefaults = [],
    ) {
    }

    /**
     * Returns a copy carrying defaults from the registered Joomla route.
     *
     * @param   array<string, mixed>  $routeDefaults  Defaults required by the target controller.
     *
     * @return  self
     *
     * @since   __DEPLOY_VERSION__
     */
    public function withRouteDefaults(array $routeDefaults): self
    {
        return new self(
            operationId: $this->operationId,
            method: $this->method,
            path: $this->path,
            controller: $this->controller,
            task: $this->task,
            title: $this->title,
            description: $this->description,
            inputSchema: $this->inputSchema,
            outputSchema: $this->outputSchema,
            requestBodySchema: $this->requestBodySchema,
            queryParameters: $this->queryParameters,
            pathParameters: $this->pathParameters,
            acl: $this->acl,
            annotations: $this->annotations,
            exposeToMcp: $this->exposeToMcp,
            public: $this->public,
            successStatus: $this->successStatus,
            tags: $this->tags,
            routeDefaults: $routeDefaults,
        );
    }
}
