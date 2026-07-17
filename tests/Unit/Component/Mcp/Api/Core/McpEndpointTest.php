<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\Mcp\Api\Core;

use Joomla\CMS\Mcp\Resource\ResourceResult;
use Joomla\CMS\Mcp\Tool\ToolResult;
use Joomla\Component\MCP\Api\Auth\AuthServiceInterface;
use Joomla\Component\MCP\Api\Core\AbilityRegistry;
use Joomla\Component\MCP\Api\Core\McpEndpoint;
use Joomla\Tests\Unit\UnitTestCase;
use Mcp\Server\Transport\Http\HttpMessage;

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
}
