<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\OpenApi;

use Joomla\CMS\WebService\Operation\OperationDefinition;

/**
 * Projects canonical operations to an OpenAPI 3.1 document.
 *
 * @since  __DEPLOY_VERSION__
 */
final class OpenApiDocumentFactory
{
    /**
     * @param iterable<OperationDefinition> $operations
     *
     * @return array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(
        iterable $operations,
        string $title = 'Joomla Web Services API',
        string $version = '1.0.0',
    ): array {
        $document = [
            'openapi'    => '3.1.0',
            'info'       => ['title' => $title, 'version' => $version],
            'paths'      => [],
            'components' => [
                'securitySchemes' => [
                    'joomlaToken' => [
                        'type' => 'apiKey',
                        'in'   => 'header',
                        'name' => 'X-Joomla-Token',
                    ],
                ],
            ],
        ];

        foreach ($operations as $operation) {
            $path       = '/' . str_replace(':id', '{id}', $operation->path);
            $method     = strtolower($operation->method);
            $definition = [
                'operationId' => $operation->operationId,
                'summary'     => $operation->title,
                'description' => $operation->description,
                'tags'        => $operation->tags,
                'parameters'  => $this->parameters($operation),
                'responses'   => [
                    (string) $operation->successStatus => [
                        'description' => $operation->successStatus === 204 ? 'No content.' : 'Successful response.',
                    ],
                ],
            ];

            if (!$operation->public) {
                $definition['security'] = [['joomlaToken' => []]];
            }

            if ($operation->outputSchema !== null && $operation->successStatus !== 204) {
                $definition['responses'][(string) $operation->successStatus]['content']['application/json']['schema']
                    = $operation->outputSchema;
            }

            if ($operation->requestBodySchema !== []) {
                $definition['requestBody'] = [
                    'required' => true,
                    'content'  => [
                        'application/json' => ['schema' => $operation->requestBodySchema],
                    ],
                ];
            }

            $document['paths'][$path][$method] = $definition;
        }

        ksort($document['paths']);

        return $document;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parameters(OperationDefinition $operation): array
    {
        $parameters = [];

        foreach ($operation->pathParameters as $name => $parameter) {
            $parameters[] = [
                'name'     => $name,
                'in'       => 'path',
                'required' => true,
                'schema'   => $parameter['schema'],
            ];
        }

        foreach ($operation->queryParameters as $name => $parameter) {
            $definition = [
                'name'     => $name,
                'in'       => 'query',
                'required' => false,
                'schema'   => $parameter['schema'],
            ];

            if ($parameter['argument'] !== $name) {
                $definition['x-joomla-argument'] = $parameter['argument'];
            }

            $parameters[] = $definition;
        }

        return $parameters;
    }
}
