<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Core;

use Joomla\CMS\MCP\Tool\ToolInterface;
use Joomla\CMS\MCP\Tool\ToolResult;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Mcp\Server\Server;
use Mcp\Server\ServerSession;
use Mcp\Shared\ErrorData;
use Mcp\Types\CallToolRequestParams;
use Mcp\Types\JsonRpcMessage;
use Mcp\Types\JSONRPCRequest;
use Mcp\Types\RequestId;
use Mcp\Types\RequestParams;
use Mcp\Types\Result;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests client filtering through the registered MCP SDK handlers.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpEndpointToolFilterTest extends UnitTestCase
{
    /**
     * Listing and direct calls enforce the same include/exclude selection.
     *
     * @param   array  $params   Client parameters
     * @param   array  $allowed  Expected available tool names
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[DataProvider('clientSelections')]
    public function testListingAndCallingApplyTheSameSelection(array $params, array $allowed): void
    {
        $registry = new AbilityRegistry();
        $names    = ['content.articles.get', 'content.articles.delete', 'thirdparty.new'];

        foreach ($names as $name) {
            $tool = $this->createMock(ToolInterface::class);
            $tool->method('getName')->willReturn($name);
            $tool->method('getSchema')->willReturn(['inputSchema' => ['type' => 'object']]);
            $tool->expects($this->exactly(\in_array($name, $allowed, true) ? 1 : 0))->method('execute')
                ->with(['id' => 7])->willReturn(ToolResult::text($name));
            $registry->addAbility($tool);
        }

        $server = $this->createServer($registry, $params);
        $list   = $this->wire($this->dispatch($server, 'tools/list'));
        $this->assertSame($allowed, array_column($list['tools'], 'name'));

        foreach ($names as $name) {
            $result = $this->dispatch($server, 'tools/call', new CallToolRequestParams($name, ['id' => 7]));

            if (\in_array($name, $allowed, true)) {
                $this->assertInstanceOf(Result::class, $result);
                $this->assertSame($name, $this->wire($result)['content'][0]['text']);
            } else {
                $this->assertInstanceOf(ErrorData::class, $result);
                $this->assertSame(-32602, $result->code);
            }
        }

        $this->assertInstanceOf(ErrorData::class, $this->dispatch($server, 'tools/call', new CallToolRequestParams('unknown', [])));
    }

    /**
     * Includes defaults, new plugin tools, exact matching and invalid settings.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function clientSelections(): array
    {
        $all = ['content.articles.get', 'content.articles.delete', 'thirdparty.new'];

        return [
            'legacy'            => [[], $all],
            'include selected'  => [['tools_mode' => 'include', 'tools' => ['content.articles.get', 'unavailable']], ['content.articles.get']],
            'exclude selected'  => [['tools_mode' => 'exclude', 'tools' => ['content.articles.delete', 'unavailable']], ['content.articles.get', 'thirdparty.new']],
            'include empty'     => [['tools_mode' => 'include', 'tools' => []], []],
            'exclude empty'     => [['tools_mode' => 'exclude', 'tools' => []], $all],
            'include missing'   => [['tools_mode' => 'include'], []],
            'include case'      => [['tools_mode' => 'include', 'tools' => ['Content.Articles.Get']], []],
            'exclude case'      => [['tools_mode' => 'exclude', 'tools' => ['Content.Articles.Get']], $all],
            'include cleared'   => [['tools_mode' => 'include', 'tools' => ''], []],
            'exclude cleared'   => [['tools_mode' => 'exclude', 'tools' => ''], $all],
            'null mode'         => [['tools_mode' => null, 'tools' => []], $all],
            'empty mode'        => [['tools_mode' => '', 'tools' => []], $all],
            'null selection'    => [['tools_mode' => 'exclude', 'tools' => null], $all],
            'invalid mode'      => [['tools_mode' => 'other', 'tools' => []], []],
            'invalid selection' => [['tools_mode' => 'exclude', 'tools' => 'content.articles.get'], []],
            'invalid member'    => [['tools_mode' => 'exclude', 'tools' => [null]], []],
        ];
    }

    /**
     * Only the advertised tool's top-level description is omitted.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDescriptionPreferencePreservesSchemaAndResults(): void
    {
        $schema = [
            'description' => 'Read the article.',
            'inputSchema' => [
                'type'        => 'object',
                'description' => 'Article filters.',
                'properties'  => ['description' => ['type' => 'string', 'description' => 'Filter description.']],
                'default'     => ['description' => 'Literal default.'],
                'required'    => ['description'],
            ],
        ];
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('content.articles.get');
        $tool->method('getSchema')->willReturn($schema);
        $tool->expects($this->once())->method('execute')->with([])
            ->willReturn(ToolResult::structured(['description' => 'Article content.']));
        $registry = new AbilityRegistry();
        $registry->addAbility($tool);
        $reduced = $this->createServer($registry, ['exclude_descriptions' => 1]);
        $full    = $this->createServer($registry);

        $reducedDefinition = $this->wire($this->dispatch($reduced, 'tools/list'))['tools'][0];
        $fullDefinition    = $this->wire($this->dispatch($full, 'tools/list'))['tools'][0];
        $this->assertArrayNotHasKey('description', $reducedDefinition);
        $this->assertEquals($schema['inputSchema'], $reducedDefinition['inputSchema']);
        $this->assertSame('Read the article.', $fullDefinition['description']);
        $this->assertSame($schema, $tool->getSchema());

        $result = $this->wire($this->dispatch($reduced, 'tools/call', new CallToolRequestParams('content.articles.get', [])));
        $this->assertSame(['description' => 'Article content.'], $result['structuredContent']);
    }

    /**
     * A plugin's schema cannot advertise a name outside the saved selection.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAdvertisedNameMatchesTheRegisteredTool(): void
    {
        $tool = $this->createStub(ToolInterface::class);
        $tool->method('getName')->willReturn('thirdparty.actual');
        $tool->method('getSchema')->willReturn(['name' => 'thirdparty.alias', 'inputSchema' => ['type' => 'object']]);
        $registry = new AbilityRegistry();
        $registry->addAbility($tool);
        $server = $this->createServer($registry, ['tools_mode' => 'include', 'tools' => ['thirdparty.actual']]);

        $this->assertSame(['thirdparty.actual'], array_column($this->wire($this->dispatch($server, 'tools/list'))['tools'], 'name'));
    }

    /**
     * Register the endpoint's handlers on an SDK server.
     *
     * @param   AbilityRegistry  $registry  Registered abilities
     * @param   array            $params    Client parameters
     *
     * @return  Server
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createServer(AbilityRegistry $registry, array $params = []): Server
    {
        $endpoint = new McpEndpoint($registry, $this->createStub(AuthServiceInterface::class));
        $server   = new Server('Tool filter tests');
        (new \ReflectionMethod(McpEndpoint::class, 'registerAbilities'))->invoke($endpoint, $server, $registry, new Registry($params));

        return $server;
    }

    /**
     * Dispatch a protocol request and capture the response sent to the SDK session.
     *
     * @param   Server              $server  Registered server
     * @param   string              $method  Protocol method
     * @param   RequestParams|null  $params  Protocol parameters
     *
     * @return  Result|ErrorData
     *
     * @since   __DEPLOY_VERSION__
     */
    private function dispatch(Server $server, string $method, ?RequestParams $params = null): Result|ErrorData
    {
        $response = null;
        $session  = $this->createMock(ServerSession::class);
        $session->expects($this->once())->method('sendResponse')
            ->willReturnCallback(static function (RequestId $id, Result|ErrorData $result) use (&$response): void {
                $response = $result;
            });
        $server->setSession($session);
        $server->handleMessage(new JsonRpcMessage(new JSONRPCRequest('2.0', new RequestId(1), $params, $method)));
        $this->assertNotNull($response);

        return $response;
    }

    /**
     * Serialize SDK results as the MCP transport does.
     *
     * @param   Result|ErrorData  $result  Dispatched result
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    private function wire(Result|ErrorData $result): array
    {
        return json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
