<?php

/**
 * Standalone smoke test for the article operation vertical slice.
 */

namespace {
    \define('_JEXEC', 1);

    spl_autoload_register(
        static function (string $class): void {
            $prefixes = [
                'Joomla\\CMS\\WebService\\'         => __DIR__ . '/../libraries/src/WebService/',
                'Joomla\\Component\\Content\\Api\\' => __DIR__ . '/../api/components/com_content/src/',
                'Joomla\\Component\\MCP\\Api\\'     => __DIR__ . '/../api/components/com_mcp/src/',
            ];

            foreach ($prefixes as $prefix => $directory) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }

                $file = $directory . str_replace('\\', '/', substr($class, \strlen($prefix))) . '.php';

                if (is_file($file)) {
                    require $file;
                }
            }
        },
    );
}

namespace Doctrine\Inflector {
    final class InflectorFactory
    {
        public static function create(): self
        {
            return new self();
        }

        public function build(): object
        {
            return new class () {
                public function singularize(string $word): string
                {
                    return str_ends_with($word, 's') ? substr($word, 0, -1) : $word;
                }
            };
        }
    }
}

namespace Joomla\CMS\MVC\Controller {
    class ApiController
    {
    }
}

namespace Joomla\Router {
    final class Route
    {
        public function __construct(
            public array $methods,
            public string $route,
            public string $controller,
            public array $rules = [],
            public array $defaults = [],
        ) {
        }
    }
}

namespace Joomla\Component\MCP\Api\Core {
    final class AbilityRegistry
    {
        /** @var array<string, object> */
        public array $abilities = [];

        public function addAbility(object $ability): void
        {
            $this->abilities[$ability->getName()] = $ability;
        }
    }
}

namespace Joomla\Component\MCP\Api\Tool {
    interface ToolInterface
    {
        public function getName(): string;

        public function getSchema(): array;

        public function execute(array $params): \Mcp\Types\CallToolResult;
    }
}

namespace Mcp\Types {
    final class TextContent
    {
        public function __construct(public string $text)
        {
        }
    }

    final class CallToolResult
    {
        public function __construct(
            public array $content,
            public bool $isError = false,
            public mixed $meta = null,
            public mixed $structuredContent = null,
        ) {
        }
    }
}

namespace {
    use Joomla\CMS\WebService\Internal\InternalApiDispatcherInterface;
    use Joomla\CMS\WebService\Internal\InternalApiResponse;
    use Joomla\CMS\WebService\OpenApi\OpenApiDocumentFactory;
    use Joomla\CMS\WebService\Operation\OperationArgumentMapper;
    use Joomla\CMS\WebService\Operation\OperationCompiler;
    use Joomla\CMS\WebService\Operation\OperationDefinition;
    use Joomla\CMS\WebService\Operation\OperationInput;
    use Joomla\CMS\WebService\Operation\RestRouteFactory;
    use Joomla\CMS\WebService\Resource\ResourceProfile;
    use Joomla\CMS\WebService\Resource\Schema\ResourceSchemaFactory;
    use Joomla\Component\Content\Api\Controller\ArticlesController;
    use Joomla\Component\Content\Api\Resource\Article;
    use Joomla\Component\MCP\Api\Core\AbilityRegistry;
    use Joomla\Component\MCP\Api\Tool\HttpOperationInvoker;
    use Joomla\Component\MCP\Api\Tool\InternalApiOperationInvoker;
    use Joomla\Component\MCP\Api\Tool\OperationInvokerInterface;
    use Joomla\Component\MCP\Api\Tool\OperationResult;
    use Joomla\Component\MCP\Api\Tool\WebserviceToolProvider;

    $assert = static function (bool $condition, string $message): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    };

    $schemaFactory = new ResourceSchemaFactory();
    $createSchema  = $schemaFactory->create(Article::class, ResourceProfile::CREATE);
    $updateSchema  = $schemaFactory->create(Article::class, ResourceProfile::UPDATE);

    $assert(
        $createSchema['required'] === ['title', 'articletext', 'category'],
        'Unexpected create requirements.',
    );
    $assert(!isset($createSchema['properties']['id']), 'Guarded ID leaked into the create schema.');
    $assert(($updateSchema['minProperties'] ?? null) === 1, 'PATCH must require one changed property.');

    /** @var Article $patch */
    $patch = Article::fromArray(
        ['title' => 'Changed title', 'note' => null, 'custom_colour' => 'blue'],
        ResourceProfile::UPDATE,
    );
    $assert($patch->has('title'), 'The supplied title was not initialised.');
    $assert($patch->has('note') && $patch->note === null, 'Explicit null was not preserved.');
    $assert(!$patch->has('alias'), 'An omitted property was initialised.');
    $assert(
        $patch->getAdditionalProperties()['custom_colour'] === 'blue',
        'An installation-specific property was not preserved.',
    );
    $normalisedPatch = $patch->toArray(ResourceProfile::UPDATE);
    $assert(!\array_key_exists('alias', $normalisedPatch), 'An omitted default leaked into PATCH output.');
    $assert(\array_key_exists('note', $normalisedPatch), 'Explicit null disappeared during normalisation.');

    $compiler   = new OperationCompiler($schemaFactory);
    $operations = $compiler->compile(ArticlesController::class);
    $assert(\count($operations) === 5, 'The compiler did not create five CRUD operations.');
    $assert($operations[3]->operationId === 'content.articles.update', 'Unexpected update operation ID.');
    $assert(
        $operations[0]->queryParameters['filter[author]']['argument'] === 'author',
        'Filter mapping is missing.',
    );
    $assert(
        $operations[0]->queryParameters['list[ordering]']['argument'] === 'ordering',
        'List mapping is missing.',
    );

    $mappedInput = (new OperationArgumentMapper())->map(
        $operations[3],
        ['id' => 7, 'title' => 'Changed title'],
    );
    $assert($mappedInput->path === ['id' => 7], 'The path argument mapping is incorrect.');
    $assert($mappedInput->body === ['title' => 'Changed title'], 'The request body mapping is incorrect.');

    $mappedCategoryInput = (new OperationArgumentMapper())->map(
        $operations[3],
        ['id' => 7, 'category' => 2],
    );
    $assert($mappedCategoryInput->body === ['catid' => 2], 'The REST source-name mapping is incorrect.');

    $routeFactory = new RestRouteFactory();
    $routes       = array_map($routeFactory->create(...), $operations);
    $assert(\count($routes) === 5, 'The REST projection did not create five routes.');
    $assert($routes[3]->route === 'v1/content/articles/:id', 'The REST update route is incorrect.');
    $assert($routes[3]->controller === 'articles.edit', 'The REST update task is incorrect.');

    $openApi = (new OpenApiDocumentFactory())->create($operations);
    $assert(
        $openApi['paths']['/v1/content/articles/{id}']['patch']['operationId']
            === 'content.articles.update',
        'The OpenAPI update projection is incorrect.',
    );
    $openApiCreateSchema = $openApi['paths']['/v1/content/articles']['post']['requestBody']['content']
        ['application/json']['schema'];
    $assert(isset($openApiCreateSchema['properties']['catid']), 'The OpenAPI REST schema is missing catid.');
    $assert(!isset($openApiCreateSchema['properties']['category']), 'A canonical property leaked into REST OpenAPI.');

    $capturedRequest = null;
    $httpInvoker     = new HttpOperationInvoker(
        new OperationArgumentMapper(),
        'https://example.test/api/index.php/',
        static fn (): string => 'test-token',
        static function (
            string $method,
            string $url,
            array $headers,
            ?string $body,
            int $timeout,
        ) use (&$capturedRequest): OperationResult {
            $capturedRequest = compact('method', 'url', 'headers', 'body', 'timeout');

            return new OperationResult(
                200,
                ['data' => ['id' => '7', 'attributes' => ['title' => 'Changed title']]],
                'application/vnd.api+json',
            );
        },
    );
    $httpResult = $httpInvoker->invoke(
        $operations[3],
        ['id' => 7, 'title' => 'Changed title', 'category' => 2],
    );
    $assert(
        $capturedRequest['url'] === 'https://example.test/api/index.php/v1/content/articles/7',
        'The HTTP invoker URL is incorrect.',
    );
    $assert(
        json_decode($capturedRequest['body'], true, 512, JSON_THROW_ON_ERROR)
            === ['title' => 'Changed title', 'catid' => 2],
        'The HTTP invoker body is incorrect.',
    );
    $assert($capturedRequest['headers']['X-Joomla-Token'] === 'test-token', 'The token was not forwarded.');
    $assert($httpResult->body === ['title' => 'Changed title', 'id' => 7], 'JSON:API was not normalised.');

    $capturedInternalInput = null;
    $internalDispatcher    = new class ($capturedInternalInput) implements InternalApiDispatcherInterface {
        public function __construct(private mixed &$capturedInput)
        {
        }

        public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse
        {
            $this->capturedInput = $input;

            return new InternalApiResponse(
                200,
                ['data' => ['id' => '7', 'attributes' => ['title' => 'Changed title']]],
            );
        }
    };
    $internalResult = (new InternalApiOperationInvoker(new OperationArgumentMapper(), $internalDispatcher))->invoke(
        $operations[3],
        ['id' => 7, 'title' => 'Changed title', 'category' => 2],
    );
    $assert($capturedInternalInput->path === ['id' => 7], 'The internal path mapping is incorrect.');
    $assert(
        $capturedInternalInput->body === ['title' => 'Changed title', 'catid' => 2],
        'The internal request body mapping is incorrect.',
    );
    $assert($internalResult->body === ['title' => 'Changed title', 'id' => 7], 'Internal JSON:API was not normalised.');

    $invoker = new class () implements OperationInvokerInterface {
        public function invoke(OperationDefinition $operation, array $arguments): OperationResult
        {
            return new OperationResult(
                200,
                ['operation' => $operation->operationId, 'arguments' => $arguments],
                'application/json',
            );
        }
    };

    $registry = new AbilityRegistry();
    $provider = new WebserviceToolProvider($compiler, $invoker);
    $provider->register($registry, ArticlesController::class);

    $assert(\count($registry->abilities) === 5, 'The provider did not register five MCP tools.');
    $tool = $registry->abilities['content.articles.update'];
    $assert($tool->getName() === 'content.articles.update', 'Unexpected MCP tool name.');
    $assert(isset($tool->getSchema()['inputSchema']['properties']['title']), 'MCP input schema is incomplete.');

    $result = $tool->execute(['id' => 7, 'title' => 'Changed title']);
    $assert(!$result->isError, 'The MCP tool returned an error.');
    $assert(
        $result->structuredContent['operation'] === 'content.articles.update',
        'The structured MCP result is incorrect.',
    );

    file_put_contents(
        __DIR__ . '/../article-openapi.json',
        json_encode($openApi, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    );

    $toolSchemas = [];

    foreach ($registry->abilities as $registeredTool) {
        $toolSchemas[] = ['name' => $registeredTool->getName()] + $registeredTool->getSchema();
    }

    file_put_contents(
        __DIR__ . '/../article-mcp-tools.json',
        json_encode(['tools' => $toolSchemas], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    );

    echo "Article operation chain smoke test passed.\n";
}
