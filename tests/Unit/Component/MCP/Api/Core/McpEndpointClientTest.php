<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Core;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MCP\Tool\ToolInterface;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Joomla\Component\MCP\Api\Auth\TokenInfo;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Session\SessionInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Mcp\Server\Transport\Http\HttpMessage;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests that HTTP requests use the authenticated client's saved tool selection.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpEndpointClientTest extends UnitTestCase
{
    /**
     * Two tokens sharing a Joomla identity must still have distinct tool access.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAuthenticatedTokenControlsListingAndDirectCalls(): void
    {
        $previousApplication = Factory::$application;
        $temporaryDirectory  = sys_get_temp_dir() . '/joomla-mcp-client-test-' . bin2hex(random_bytes(8));
        mkdir($temporaryDirectory);

        try {
            $application = $this->createStub(CMSApplication::class);
            $application->method('getSession')->willReturn($this->createStub(SessionInterface::class));
            Factory::$application = $application;

            $tokens = [
                'reader'  => TokenInfo::fromArray(['params' => '{"tools_mode":"include","tools":["content.articles.get"]}']),
                'blocked' => TokenInfo::fromArray(['params' => '{"tools_mode":"exclude","tools":["content.articles.get"]}']),
            ];
            $auth = $this->createMock(AuthServiceInterface::class);
            $auth->expects($this->exactly(5))->method('validateToken')->willReturnCallback(
                static fn (string $token): ?TokenInfo => $tokens[$token] ?? null
            );
            $tool = $this->createMock(ToolInterface::class);
            $tool->method('getName')->willReturn('content.articles.get');
            $tool->method('getSchema')->willReturn(['inputSchema' => ['type' => 'object']]);
            $tool->expects($this->never())->method('execute');
            $registry = new AbilityRegistry();
            $registry->addAbility($tool);
            $endpoint = new McpEndpoint($registry, $auth, ['tmp_dir' => $temporaryDirectory]);

            $initialize = $this->request($endpoint, 'reader', [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'initialize',
                'params'  => [
                    'protocolVersion' => '2025-03-26',
                    'capabilities'    => (object) [],
                    'clientInfo'      => ['name' => 'Client filter test', 'version' => '1.0'],
                ],
            ]);
            $this->assertSame(200, $initialize->getStatusCode(), (string) $initialize->getBody());
            $sessionId = $initialize->getHeaderLine('Mcp-Session-Id');
            $this->assertNotSame('', $sessionId);

            $this->request($endpoint, 'reader', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $sessionId);
            $listRequest = ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'];
            $readerList  = $this->request($endpoint, 'reader', $listRequest, $sessionId);
            $readerData  = json_decode((string) $readerList->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(['content.articles.get'], array_column($readerData['result']['tools'], 'name'));

            // Reusing an MCP session does not grant the previous token's selection.
            $blockedList = $this->request($endpoint, 'blocked', $listRequest, $sessionId);
            $blockedData = json_decode((string) $blockedList->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([], $blockedData['result']['tools']);

            $denied = $this->request($endpoint, 'blocked', [
                'jsonrpc' => '2.0',
                'id'      => 3,
                'method'  => 'tools/call',
                'params'  => ['name' => 'content.articles.get', 'arguments' => (object) []],
            ], $sessionId);
            $error = json_decode((string) $denied->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(-32602, $error['error']['code']);
        } finally {
            Factory::$application = $previousApplication;

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($temporaryDirectory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($temporaryDirectory);
        }
    }

    /**
     * Submit an authenticated JSON-RPC HTTP request through the endpoint.
     *
     * @param   McpEndpoint  $endpoint   Endpoint under test
     * @param   string       $token      Authenticated client token
     * @param   array        $payload    JSON-RPC payload
     * @param   string       $sessionId  Existing MCP session identifier
     *
     * @return  ResponseInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    private function request(McpEndpoint $endpoint, string $token, array $payload, string $sessionId = ''): ResponseInterface
    {
        $request = new HttpMessage(json_encode($payload, JSON_THROW_ON_ERROR));
        $request->setMethod('POST');
        $request->setHeader('Authorization', 'Bearer ' . $token);
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('Accept', 'application/json');

        if ($sessionId !== '') {
            $request->setHeader('Mcp-Session-Id', $sessionId);
            $request->setHeader('Mcp-Protocol-Version', '2025-03-26');
        }

        return $endpoint->handle($request);
    }
}
