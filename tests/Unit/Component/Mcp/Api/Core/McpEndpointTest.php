<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\Mcp\Api\Core;

use Joomla\CMS\Mcp\Resource\ResourceInterface;
use Joomla\CMS\Mcp\Resource\ResourceResult;
use Joomla\CMS\Mcp\Resource\ResourceTemplateInterface;
use Joomla\CMS\Mcp\Tool\ToolInterface;
use Joomla\CMS\Mcp\Tool\ToolResult;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Tests\Unit\UnitTestCase;
use Mcp\Server\Transport\Http\HttpMessage;
use Psr\Log\LoggerInterface;

/**
 * Test class for the McpEndpoint result conversion to the MCP wire format.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class McpEndpointTest extends UnitTestCase
{
    /**
     * Convert a ToolResult through the private endpoint converter and return the wire format
     *
     * @param ToolResult|ResourceResult $result  The result to convert
     *
     * @return array  The JSON wire format as associative array
     *
     * @since  __DEPLOY_VERSION__
     */
    private function toWireFormat(ToolResult|ResourceResult $result): array
    {
        $endpoint = new McpEndpoint(
            $this->createMock(AbilityRegistry::class),
            $this->createMock(AuthServiceInterface::class)
        );

        $method = $result instanceof ToolResult ? 'toCallToolResult' : 'toReadResourceResult';

        $converted = (new \ReflectionMethod(McpEndpoint::class, $method))->invoke($endpoint, $result);

        return json_decode(json_encode($converted), true);
    }

    /**
     * Invoke the private extractToken method with a prepared request
     *
     * @param HttpMessage $request  The request to extract the token from
     *
     * @return  string|null  The extracted token
     *
     * @since  __DEPLOY_VERSION__
     */
    private function extractToken(HttpMessage $request): ?string
    {
        $endpoint = new McpEndpoint(
            $this->createMock(AbilityRegistry::class),
            $this->createMock(AuthServiceInterface::class)
        );

        return (new \ReflectionMethod(McpEndpoint::class, 'extractToken'))->invoke($endpoint, $request);
    }

    /**
     * The Bearer token is read from the Authorization header.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testExtractTokenReadsBearerHeader(): void
    {
        $request = new HttpMessage();
        $request->setHeader('Authorization', 'Bearer abc123');

        $this->assertSame('abc123', $this->extractToken($request));
    }

    /**
     * A token supplied only as a query parameter is ignored to avoid leaking it into logs.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testExtractTokenIgnoresQueryParameter(): void
    {
        $request = new HttpMessage();
        $request->setQueryParams(['token' => 'query-token']);

        $this->assertNull($this->extractToken($request));
    }

    /**
     * With no Authorization header present, no token is returned.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testExtractTokenReturnsNullWithoutHeader(): void
    {
        $this->assertNull($this->extractToken(new HttpMessage()));
    }

    /**
     * A request without an authentication token must be rejected and must never
     * reach the ability registry. Guards against the removed ?test=auth bypass.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testRequestWithoutTokenIsRejected(): void
    {
        // Ensure no ambient Authorization header leaks in from the environment
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);

        $authService = $this->createMock(AuthServiceInterface::class);
        $authService->expects($this->never())->method('validateToken');

        $endpoint = new McpEndpoint($this->createMock(AbilityRegistry::class), $authService);

        $request = new HttpMessage();
        $request->setMethod('POST');
        $request->setQueryParams(['test' => 'auth']);

        $response = $endpoint->handle($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTextToolResultConversion(): void
    {
        $this->assertSame(
            ['content' => [['type' => 'text', 'text' => 'hello']], 'isError' => false],
            $this->toWireFormat(ToolResult::text('hello'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testErrorToolResultConversion(): void
    {
        $this->assertSame(
            ['content' => [['type' => 'text', 'text' => 'boom']], 'isError' => true],
            $this->toWireFormat(ToolResult::error('boom'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testStructuredToolResultConversion(): void
    {
        $data = ['items' => [['id' => 1, 'title' => 'Test']]];

        $this->assertSame(
            [
                'content'           => [['type' => 'text', 'text' => json_encode($data)]],
                'isError'           => false,
                'structuredContent' => $data,
            ],
            $this->toWireFormat(ToolResult::structured($data))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testImageToolResultConversion(): void
    {
        $this->assertSame(
            ['content' => [['type' => 'image', 'data' => 'aW1n', 'mimeType' => 'image/png']], 'isError' => false],
            $this->toWireFormat(ToolResult::image('aW1n', 'image/png'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testAudioToolResultConversion(): void
    {
        $this->assertSame(
            ['content' => [['type' => 'audio', 'data' => 'c25k', 'mimeType' => 'audio/wav']], 'isError' => false],
            $this->toWireFormat(ToolResult::audio('c25k', 'audio/wav'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testResourceLinkToolResultConversion(): void
    {
        $this->assertSame(
            [
                'content' => [
                    [
                        'type'        => 'resource_link',
                        'uri'         => 'joomla://media/logo.png',
                        'name'        => 'logo.png',
                        'description' => 'Site logo',
                        'mimeType'    => 'image/png',
                    ],
                ],
                'isError' => false,
            ],
            $this->toWireFormat(ToolResult::resourceLink('joomla://media/logo.png', 'logo.png', 'Site logo', 'image/png'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testEmbeddedTextToolResultConversion(): void
    {
        $this->assertSame(
            [
                'content' => [
                    [
                        'type'     => 'resource',
                        'resource' => [
                            'uri'      => 'joomla://config',
                            'mimeType' => 'application/json',
                            'text'     => '{"sitename":"x"}',
                        ],
                    ],
                ],
                'isError' => false,
            ],
            $this->toWireFormat(ToolResult::embeddedText('joomla://config', '{"sitename":"x"}', 'application/json'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testEmbeddedBlobToolResultConversion(): void
    {
        $this->assertSame(
            [
                'content' => [
                    [
                        'type'     => 'resource',
                        'resource' => [
                            'uri'      => 'joomla://media/logo.png',
                            'mimeType' => 'image/png',
                            'blob'     => 'YmxvYg==',
                        ],
                    ],
                ],
                'isError' => false,
            ],
            $this->toWireFormat(ToolResult::embeddedBlob('joomla://media/logo.png', 'YmxvYg==', 'image/png'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTextResourceResultConversion(): void
    {
        $this->assertSame(
            [
                'contents' => [
                    [
                        'uri'      => 'joomla://config',
                        'mimeType' => 'application/json',
                        'text'     => '{"sitename":"x"}',
                    ],
                ],
            ],
            $this->toWireFormat(ResourceResult::text('joomla://config', '{"sitename":"x"}', 'application/json'))
        );
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testBlobResourceResultConversion(): void
    {
        $this->assertSame(
            [
                'contents' => [
                    [
                        'uri'      => 'joomla://media/logo.png',
                        'mimeType' => 'image/png',
                        'blob'     => 'YmxvYg==',
                    ],
                ],
            ],
            $this->toWireFormat(ResourceResult::blob('joomla://media/logo.png', 'YmxvYg==', 'image/png'))
        );
    }

    /**
     * A single invalid resource template (empty name/uriTemplate) must not break the whole
     * resources/templates/list response; valid templates must still be returned. See issue #33.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testInvalidResourceTemplateIsSkipped(): void
    {
        $valid   = $this->createResourceTemplate('article', 'joomla://articles/{id}');
        $invalid = $this->createResourceTemplate('', '');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $endpoint = new McpEndpoint(
            $this->createMock(AbilityRegistry::class),
            $this->createMock(AuthServiceInterface::class),
            ['logger' => $logger]
        );

        $converted = (new \ReflectionMethod(McpEndpoint::class, 'toListResourceTemplatesResult'))
            ->invoke($endpoint, [$valid, $invalid]);

        $wire = json_decode(json_encode($converted), true);

        $this->assertCount(1, $wire['resourceTemplates']);
        $this->assertSame('article', $wire['resourceTemplates'][0]['name']);
        $this->assertSame('joomla://articles/{id}', $wire['resourceTemplates'][0]['uriTemplate']);
    }

    /**
     * A single tool with an empty name is skipped so it cannot break tools/list.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testInvalidToolIsSkipped(): void
    {
        $valid   = $this->createTool('purgeCache');
        $invalid = $this->createTool('');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $endpoint = new McpEndpoint(
            $this->createMock(AbilityRegistry::class),
            $this->createMock(AuthServiceInterface::class),
            ['logger' => $logger]
        );

        $converted = (new \ReflectionMethod(McpEndpoint::class, 'toListToolsResult'))
            ->invoke($endpoint, [$valid, $invalid]);

        $wire = json_decode(json_encode($converted), true);

        $this->assertCount(1, $wire['tools']);
        $this->assertSame('purgeCache', $wire['tools'][0]['name']);
    }

    /**
     * A single invalid resource (empty uri/name) must not break the whole resources/list
     * response; valid resources must still be returned. See issue #33.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testInvalidResourceIsSkipped(): void
    {
        $valid   = $this->createResource('joomla://config', 'config');
        $invalid = $this->createResource('', '');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $endpoint = new McpEndpoint(
            $this->createMock(AbilityRegistry::class),
            $this->createMock(AuthServiceInterface::class),
            ['logger' => $logger]
        );

        $converted = (new \ReflectionMethod(McpEndpoint::class, 'toListResourcesResult'))
            ->invoke($endpoint, [$valid, $invalid]);

        $wire = json_decode(json_encode($converted), true);

        $this->assertCount(1, $wire['resources']);
        $this->assertSame('joomla://config', $wire['resources'][0]['uri']);
        $this->assertSame('config', $wire['resources'][0]['name']);
    }

    /**
     * Build a resource template stub with the given name and uriTemplate.
     *
     * @param string $name         The template name
     * @param string $uriTemplate  The URI template
     *
     * @return  ResourceTemplateInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createResourceTemplate(string $name, string $uriTemplate): ResourceTemplateInterface
    {
        $template = $this->createMock(ResourceTemplateInterface::class);
        $template->method('getName')->willReturn($name);
        $template->method('getUriTemplate')->willReturn($uriTemplate);
        $template->method('getTitle')->willReturn('');
        $template->method('getDescription')->willReturn('');
        $template->method('getMimeType')->willReturn('');

        return $template;
    }

    /**
     * Build a tool mock reporting the given name and an empty schema.
     *
     * @param string $name  The tool name
     *
     * @return ToolInterface
     *
     * @since  __DEPLOY_VERSION__
     */
    private function createTool(string $name): ToolInterface
    {
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn($name);
        $tool->method('getSchema')->willReturn(['inputSchema' => ['type' => 'object']]);

        return $tool;
    }

    /**
     * Build a resource stub with the given uri and name.
     *
     * @param string $uri   The resource URI
     * @param string $name  The resource name
     *
     * @return  ResourceInterface
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createResource(string $uri, string $name): ResourceInterface
    {
        $resource = $this->createMock(ResourceInterface::class);
        $resource->method('getUri')->willReturn($uri);
        $resource->method('getName')->willReturn($name);
        $resource->method('getTitle')->willReturn('');
        $resource->method('getDescription')->willReturn('');
        $resource->method('getMimeType')->willReturn('');

        return $resource;
    }
}
