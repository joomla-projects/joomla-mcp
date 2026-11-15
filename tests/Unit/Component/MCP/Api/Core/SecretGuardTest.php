<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Core;

use Joomla\Component\MCP\Api\Core\SecretGuard;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for the SecretGuard.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class SecretGuardTest extends UnitTestCase
{
    /**
     * A secret is detected as a substring of a plain string.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDetectsSecretInString(): void
    {
        $guard = new SecretGuard(['s3cr3t-value']);

        $this->assertTrue($guard->containsSecret('Here it is: s3cr3t-value, enjoy'));
        $this->assertFalse($guard->containsSecret('Nothing to see here'));
    }

    /**
     * Secrets are found in nested arrays and objects, in values as well as in keys.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDetectsSecretInNestedStructures(): void
    {
        $guard = new SecretGuard(['s3cr3t-value']);

        $nestedArray  = ['title' => 'Hello', 'fields' => [['name' => 'note', 'value' => 'x s3cr3t-value y']]];
        $nestedObject = ['data' => (object) ['attributes' => (object) ['articletext' => 's3cr3t-value']]];
        $inKey        = ['metadata' => ['s3cr3t-value' => 'harmless']];

        $this->assertTrue($guard->containsSecret($nestedArray));
        $this->assertTrue($guard->containsSecret($nestedObject));
        $this->assertTrue($guard->containsSecret($inKey));
        $this->assertFalse($guard->containsSecret(['title' => 'Hello', 'catid' => 2, 'state' => true, 'note' => null]));
    }

    /**
     * Values shorter than the minimum length and non-string values are ignored.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testIgnoresShortAndNonStringSecrets(): void
    {
        $guard = new SecretGuard(['short', '   ', '', null, 12345678, ['array-value']]);

        $this->assertFalse($guard->containsSecret('short text with 12345678 and array-value'));
    }

    /**
     * Without any secrets nothing is ever detected.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testEmptyGuardNeverDetects(): void
    {
        $this->assertFalse((new SecretGuard([]))->containsSecret(['anything' => 'at all']));
    }

    /**
     * The token, its decoded form, the configuration secrets and the additional values are all protected.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFromConfigurationCollectsAllSecrets(): void
    {
        $config = new Registry([
            'secret'   => 'site-secret-word',
            'password' => 'db-password-1',
            'smtppass' => 'smtp-password-1',
            'sitename' => 'My public site name',
        ]);

        $token = base64_encode('sha256:42:0123456789abcdef');
        $guard = SecretGuard::fromConfiguration($config, $token, "extra-secret-one\r\n\nextra-secret-two\nshort");

        $this->assertTrue($guard->containsSecret($token));
        $this->assertTrue($guard->containsSecret('sha256:42:0123456789abcdef'));
        $this->assertTrue($guard->containsSecret('site-secret-word'));
        $this->assertTrue($guard->containsSecret('db-password-1'));
        $this->assertTrue($guard->containsSecret('smtp-password-1'));
        $this->assertTrue($guard->containsSecret('extra-secret-one'));
        $this->assertTrue($guard->containsSecret('extra-secret-two'));

        // Non-secret configuration values and short additional values are not protected
        $this->assertFalse($guard->containsSecret('My public site name'));
        $this->assertFalse($guard->containsSecret('short'));
    }

    /**
     * A missing token and an empty configuration are handled gracefully.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testFromConfigurationWithoutToken(): void
    {
        $guard = SecretGuard::fromConfiguration(new Registry(), null);

        $this->assertFalse($guard->containsSecret(['title' => 'Anything']));
    }

    /**
     * Unreasonably deep structures are refused rather than partially inspected.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testRefusesExcessivelyDeepStructures(): void
    {
        $value = 'harmless';

        for ($i = 0; $i < 100; $i++) {
            $value = [$value];
        }

        $this->assertTrue((new SecretGuard(['s3cr3t-value']))->containsSecret($value));
    }
}
