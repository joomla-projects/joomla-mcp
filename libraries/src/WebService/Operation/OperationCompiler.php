<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

use Doctrine\Inflector\InflectorFactory;
use Joomla\CMS\WebService\Operation\Attribute\ResourceOperations;
use Joomla\CMS\WebService\Resource\ResourceProfile;
use Joomla\CMS\WebService\Resource\Schema\ResourceSchemaFactory;

/**
 * Compiles convention-derived CRUD operations from an attributed Joomla API controller.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OperationCompiler
{
    public function __construct(private readonly ResourceSchemaFactory $schemaFactory = new ResourceSchemaFactory())
    {
    }

    /**
     * @param class-string $controllerClass
     *
     * @return list<OperationDefinition>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function compile(string $controllerClass): array
    {
        $reflection = new \ReflectionClass($controllerClass);
        $attributes = $reflection->getAttributes(ResourceOperations::class);

        if ($attributes === []) {
            return [];
        }

        /** @var ResourceOperations $configuration */
        $configuration = $attributes[0]->newInstance();
        $convention = $this->deriveConvention($reflection, $configuration);
        $resourceClass = $convention['resourceClass'];
        $queryClass = $convention['queryClass'];
        $basePath = $convention['basePath'];
        $controller = $convention['controller'];
        $prefix = $convention['operationPrefix'];
        $component = $convention['component'];
        $tag = ucfirst($convention['collection']);

        $readSchema = $this->schemaFactory->create($resourceClass, ResourceProfile::READ);
        $listSchema = $this->schemaFactory->create($resourceClass, ResourceProfile::LIST);
        $createSchema = $this->schemaFactory->create($resourceClass, ResourceProfile::CREATE);
        $updateSchema = $this->schemaFactory->create($resourceClass, ResourceProfile::UPDATE);
        $querySchema = class_exists($queryClass)
            ? $this->schemaFactory->create($queryClass)
            : $this->emptyObjectSchema();
        $idSchema = ['type' => 'integer', 'minimum' => 1, 'description' => 'The resource identifier.'];

        return [
            new OperationDefinition(
                operationId: $prefix . '.list',
                method: 'GET',
                path: $basePath,
                controller: $controller,
                task: 'displayList',
                title: 'List ' . $convention['collection'],
                description: 'Returns a filtered list of ' . $convention['collection'] . '.',
                inputSchema: $querySchema,
                outputSchema: ['type' => 'array', 'items' => $listSchema],
                queryParameters: $this->queryParameters($querySchema),
                acl: ['component' => $component],
                annotations: $this->annotations('GET'),
                exposeToMcp: $configuration->exposeToMcp,
                public: $configuration->publicGets,
                tags: [$tag],
            ),
            new OperationDefinition(
                operationId: $prefix . '.get',
                method: 'GET',
                path: $basePath . '/:id',
                controller: $controller,
                task: 'displayItem',
                title: 'Get ' . $convention['resource'],
                description: 'Returns one ' . $convention['resource'] . ' by identifier.',
                inputSchema: $this->objectSchema(['id' => $idSchema], ['id']),
                outputSchema: $readSchema,
                pathParameters: ['id' => $this->parameter('id', $idSchema)],
                acl: ['component' => $component],
                annotations: $this->annotations('GET'),
                exposeToMcp: $configuration->exposeToMcp,
                public: $configuration->publicGets,
                tags: [$tag],
            ),
            new OperationDefinition(
                operationId: $prefix . '.create',
                method: 'POST',
                path: $basePath,
                controller: $controller,
                task: 'add',
                title: 'Create ' . $convention['resource'],
                description: 'Creates a new ' . $convention['resource'] . '.',
                inputSchema: $createSchema,
                outputSchema: $readSchema,
                requestBodySchema: $createSchema,
                acl: ['component' => $component, 'action' => 'core.create'],
                annotations: $this->annotations('POST'),
                exposeToMcp: $configuration->exposeToMcp,
                successStatus: 201,
                tags: [$tag],
            ),
            new OperationDefinition(
                operationId: $prefix . '.update',
                method: 'PATCH',
                path: $basePath . '/:id',
                controller: $controller,
                task: 'edit',
                title: 'Update ' . $convention['resource'],
                description: 'Updates selected properties of an existing ' . $convention['resource'] . '.',
                inputSchema: $this->mergeInputSchemas($idSchema, $updateSchema),
                outputSchema: $readSchema,
                requestBodySchema: $updateSchema,
                pathParameters: ['id' => $this->parameter('id', $idSchema)],
                acl: ['component' => $component, 'action' => 'core.edit', 'resourceParameter' => 'id'],
                annotations: $this->annotations('PATCH'),
                exposeToMcp: $configuration->exposeToMcp,
                tags: [$tag],
            ),
            new OperationDefinition(
                operationId: $prefix . '.delete',
                method: 'DELETE',
                path: $basePath . '/:id',
                controller: $controller,
                task: 'delete',
                title: 'Delete ' . $convention['resource'],
                description: 'Deletes an existing ' . $convention['resource'] . '.',
                inputSchema: $this->objectSchema(['id' => $idSchema], ['id']),
                outputSchema: null,
                pathParameters: ['id' => $this->parameter('id', $idSchema)],
                acl: ['component' => $component, 'action' => 'core.delete', 'resourceParameter' => 'id'],
                annotations: $this->annotations('DELETE'),
                exposeToMcp: $configuration->exposeToMcp,
                successStatus: 204,
                tags: [$tag],
            ),
        ];
    }

    /**
     * @return array{
     *     component: string,
     *     collection: string,
     *     resource: string,
     *     resourceClass: class-string,
     *     queryClass: class-string,
     *     basePath: string,
     *     controller: string,
     *     operationPrefix: string
     * }
     */
    private function deriveConvention(\ReflectionClass $controller, ResourceOperations $configuration): array
    {
        if (!preg_match(
            '/^Joomla\\\\Component\\\\([^\\\\]+)\\\\Api\\\\Controller$/',
            $controller->getNamespaceName(),
            $matches,
        )) {
            throw new \LogicException(
                sprintf(
                    'The controller namespace %s does not follow the Joomla API convention.',
                    $controller->getNamespaceName(),
                ),
            );
        }

        $componentName = $matches[1];
        $component = strtolower($componentName);
        $controllerName = preg_replace('/Controller$/', '', $controller->getShortName());
        $collection = strtolower($controllerName);
        $inflector = InflectorFactory::create()->build();
        $resource = $inflector->singularize($controllerName);
        $resourceClass = $configuration->resourceClass
            ?? sprintf('Joomla\\Component\\%s\\Api\\Resource\\%s', $componentName, $resource);
        $queryClass = sprintf('Joomla\\Component\\%s\\Api\\Query\\%sListQuery', $componentName, $resource);

        if (!class_exists($resourceClass)) {
            throw new \LogicException(
                sprintf('The convention-derived resource class %s does not exist.', $resourceClass),
            );
        }

        return [
            'component' => 'com_' . $component,
            'collection' => $collection,
            'resource' => strtolower($resource),
            'resourceClass' => $resourceClass,
            'queryClass' => $queryClass,
            'basePath' => $configuration->basePath ?? sprintf('v1/%s/%s', $component, $collection),
            'controller' => $configuration->controller ?? $collection,
            'operationPrefix' => sprintf('%s.%s', $component, $collection),
        ];
    }

    /**
     * Maps the transport-neutral query DTO to Joomla's established query-string convention.
     *
     * @param array<string, mixed> $querySchema
     *
     * @return array<string, array{argument: string, schema: array<string, mixed>}>
     */
    private function queryParameters(array $querySchema): array
    {
        $parameters = [];

        foreach ($querySchema['properties'] ?? [] as $name => $schema) {
            $parameterName = \in_array($name, ['ordering', 'direction'], true)
                ? sprintf('list[%s]', $name)
                : sprintf('filter[%s]', $name);

            $parameters[$parameterName] = $this->parameter($name, $schema);
        }

        return $parameters;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array{argument: string, schema: array<string, mixed>}
     */
    private function parameter(string $argument, array $schema): array
    {
        return ['argument' => $argument, 'schema' => $schema];
    }

    /**
     * @return array<string, bool>
     */
    private function annotations(string $method): array
    {
        return [
            'readOnlyHint' => \in_array($method, ['GET', 'HEAD', 'OPTIONS'], true),
            'destructiveHint' => $method === 'DELETE',
            'idempotentHint' => \in_array($method, ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'], true),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $properties
     * @param list<string> $required
     *
     * @return array<string, mixed>
     */
    private function objectSchema(array $properties, array $required = []): array
    {
        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $idSchema
     * @param array<string, mixed> $bodySchema
     *
     * @return array<string, mixed>
     */
    private function mergeInputSchemas(array $idSchema, array $bodySchema): array
    {
        $properties = ['id' => $idSchema] + ($bodySchema['properties'] ?? []);

        return $this->objectSchema($properties, ['id']);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyObjectSchema(): array
    {
        return $this->objectSchema([]);
    }
}
