<?php

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Operation;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Router\ApiRouter;
use Joomla\CMS\WebService\Operation\ControllerClassResolver;
use Joomla\CMS\WebService\Operation\OperationCompiler;
use Joomla\CMS\WebService\Operation\OperationRoute;
use Joomla\CMS\WebService\Operation\OperationRouteFactory;
use Joomla\CMS\WebService\Operation\RouterOperationDiscovery;
use Joomla\Component\Content\Api\Controller\ArticlesController;
use Joomla\Router\Route;
use PHPUnit\Framework\TestCase;

final class RouterOperationDiscoveryTest extends TestCase
{
    public function testDiscoversOperationsCarriedByGeneratedRoutes(): void
    {
        $router = $this->createRouter();
        $compiler = new OperationCompiler();
        $factory = new OperationRouteFactory();

        foreach ($compiler->compile(ArticlesController::class) as $operation) {
            $router->addRoute($factory->create($operation));
        }

        $operations = (new RouterOperationDiscovery($router, $compiler, new ControllerClassResolver()))->discover();

        self::assertSame(
            [
                'content.articles.list',
                'content.articles.get',
                'content.articles.create',
                'content.articles.update',
                'content.articles.delete',
            ],
            array_column($operations, 'operationId'),
        );
    }

    public function testGeneratedAndManualRoutesCanCoexist(): void
    {
        $router = $this->createRouter();
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[0];
        $generatedRoute = (new OperationRouteFactory())->create($operation);
        $manualRoute = new Route(
            ['GET'],
            'v1/content/articles/:id/contenthistory',
            'history.displayList',
            ['id' => '(\\d+)'],
            ['component' => 'com_contenthistory'],
        );

        $router->addRoute($generatedRoute);
        $router->addRoute($manualRoute);

        self::assertCount(2, $router->getRoutes());
        self::assertInstanceOf(OperationRoute::class, $router->getRoutes()[0]);
        self::assertSame(Route::class, $router->getRoutes()[1]::class);

        $operations = (new RouterOperationDiscovery(
            $router,
            new OperationCompiler(),
            new ControllerClassResolver(),
        ))->discover();

        self::assertCount(1, $operations);
        self::assertSame('content.articles.list', $operations[0]->operationId);
    }

    public function testOperationMetadataSurvivesRouteSerialisation(): void
    {
        $operation = (new OperationCompiler())->compile(ArticlesController::class)[3];
        $route = (new OperationRouteFactory())->create($operation);

        $restored = unserialize(serialize($route));

        self::assertInstanceOf(OperationRoute::class, $restored);
        self::assertSame($operation->operationId, $restored->getOperation()->operationId);
        self::assertSame($operation->method, $restored->getOperation()->method);
        self::assertSame($operation->path, $restored->getOperation()->path);
        self::assertSame($route->getController(), $restored->getController());
        self::assertSame($route->getDefaults(), $restored->getDefaults());
    }

    public function testLegacyRoutesLimitWhichCompiledOperationsAreExposed(): void
    {
        $router = $this->createRouter();
        $router->addRoute(
            new Route(
                ['GET'],
                'v1/content/articles',
                'articles.displayList',
                [],
                ['component' => 'com_content', 'public' => true],
            ),
        );

        $discovery = new RouterOperationDiscovery(
            $router,
            new OperationCompiler(),
            new ControllerClassResolver(),
        );
        $operations = $discovery->discover();

        self::assertCount(1, $operations);
        self::assertSame('content.articles.list', $operations[0]->operationId);
    }

    public function testPreservesControllerSpecificRouteDefaults(): void
    {
        $router = $this->createRouter();
        $router->addRoute(
            new Route(
                ['PATCH'],
                'v1/content/articles/:id',
                'articles.edit',
                ['id' => '(\\d+)'],
                ['component' => 'com_content', 'context' => 'com_content.article'],
            ),
        );

        $discovery = new RouterOperationDiscovery(
            $router,
            new OperationCompiler(),
            new ControllerClassResolver(),
        );
        $operations = $discovery->discover();

        self::assertCount(1, $operations);
        self::assertSame('content.articles.update', $operations[0]->operationId);
        self::assertSame(['context' => 'com_content.article'], $operations[0]->routeDefaults);
    }

    public function testIgnoresRoutesWithoutAnAttributedController(): void
    {
        $router = $this->createRouter();
        $router->addRoute(
            new Route(
                ['POST'],
                'v1/mcp',
                'mcp.handle',
                [],
                ['component' => 'com_mcp'],
            ),
        );

        $discovery = new RouterOperationDiscovery(
            $router,
            new OperationCompiler(),
            new ControllerClassResolver(),
        );

        self::assertSame([], $discovery->discover());
    }

    private function createRouter(): ApiRouter
    {
        return new ApiRouter($this->createMock(CMSApplicationInterface::class));
    }
}
