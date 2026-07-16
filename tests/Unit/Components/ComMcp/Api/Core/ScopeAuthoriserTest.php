<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Core;

use Joomla\CMS\OAuth\ResourceServer\AccessTokenPrincipal;
use Joomla\Component\MCP\Api\Core\InsufficientScopeException;
use Joomla\Component\MCP\Api\Core\McpRequestContext;
use Joomla\Component\MCP\Api\Core\ScopeAuthoriser;
use Joomla\Component\MCP\Api\Tool\ScopedAbilityInterface;
use Joomla\Component\MCP\Api\Tool\ToolInterface;
use Mcp\Types\CallToolResult;
use PHPUnit\Framework\TestCase;

/**
 * Tests OAuth scope enforcement.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ScopeAuthoriserTest extends TestCase
{
    public function testAllowsPrincipalWithAllScopes(): void
    {
        $authoriser = new ScopeAuthoriser();
        $tool = $this->tool(['content.articles:write']);
        $principal = $this->principal(['mcp:use', 'content.articles:write']);

        $authoriser->assertToolAccess($principal, $tool);
        self::assertTrue($authoriser->canUseTool($principal, $tool));
    }

    public function testRejectsMissingOperationScope(): void
    {
        $authoriser = new ScopeAuthoriser();
        $tool = $this->tool(['content.articles:write']);
        $principal = $this->principal(['mcp:use']);

        $this->expectException(InsufficientScopeException::class);
        $authoriser->assertToolAccess($principal, $tool);
    }

    /**
     * @param list<string> $scopes
     */
    private function principal(array $scopes): AccessTokenPrincipal
    {
        return new AccessTokenPrincipal(
            issuer: 'https://issuer.example',
            subject: '42',
            clientId: 'client',
            audiences: ['https://site.example/api/index.php/v1/mcp'],
            scopes: $scopes,
            issuedAt: new \DateTimeImmutable('-1 minute'),
            expiresAt: new \DateTimeImmutable('+5 minutes'),
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function tool(array $scopes): ToolInterface
    {
        return new class ($scopes) implements ToolInterface, ScopedAbilityInterface {
            /**
             * @param list<string> $scopes
             */
            public function __construct(private readonly array $scopes)
            {
            }

            public function getName(): string
            {
                return 'test';
            }

            public function getSchema(): array
            {
                return ['inputSchema' => ['type' => 'object']];
            }

            public function execute(array $params, McpRequestContext $context): CallToolResult
            {
                return new CallToolResult([]);
            }

            public function getRequiredScopes(): array
            {
                return $this->scopes;
            }
        };
    }
}
