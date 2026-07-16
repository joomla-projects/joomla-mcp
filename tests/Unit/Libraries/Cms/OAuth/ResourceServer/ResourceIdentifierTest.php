<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  OAuth.ResourceServer
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Tests\Unit\Libraries\Cms\OAuth\ResourceServer;

use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;
use PHPUnit\Framework\TestCase;

/**
 * Tests the protected-resource identifier.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ResourceIdentifierTest extends TestCase
{
    public function testAcceptsHttpsUri(): void
    {
        $resource = new ResourceIdentifier('https://example.org/api/index.php/v1/mcp');

        self::assertSame('https://example.org/api/index.php/v1/mcp', (string)$resource);
    }

    public function testRejectsInsecureRemoteUri(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ResourceIdentifier('http://example.org/api/index.php/v1/mcp');
    }

    public function testAllowsLoopbackForDevelopment(): void
    {
        $resource = new ResourceIdentifier('http://127.0.0.1:8080/api/index.php/v1/mcp');

        self::assertSame('http://127.0.0.1:8080/api/index.php/v1/mcp', (string)$resource);
    }
}
