<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Core;

use Joomla\Component\MCP\Api\Core\ArgumentRedactor;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for the ArgumentRedactor.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class ArgumentRedactorTest extends UnitTestCase
{
    /**
     * Redaction replaces known secrets and masks credential-like keys, leaving everything else intact.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testRedactReplacesSecretsAndMasksCredentialKeys(): void
    {
        $redactor = new ArgumentRedactor(['s3cr3t-value']);

        $redacted = $redactor->redact([
            'title'    => 'Hello',
            'text'     => 'before s3cr3t-value after',
            'password' => 'not-a-listed-secret',
            'params'   => (object) ['api_key' => 'abc', 'count' => 3],
            'metakey'  => 'joomla, cms',
            'author'   => 'Jane',
            'smtppass' => 'x',
        ]);

        $this->assertSame(
            [
                'title'    => 'Hello',
                'text'     => 'before *** after',
                'password' => '***',
                'params'   => ['api_key' => '***', 'count' => 3],
                'metakey'  => 'joomla, cms',
                'author'   => 'Jane',
                'smtppass' => '***',
            ],
            $redacted
        );
    }

    /**
     * Secrets are also replaced in nested structures and in keys.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testRedactsNestedValuesAndKeys(): void
    {
        $redactor = new ArgumentRedactor(['s3cr3t-value']);

        $this->assertSame(
            ['fields' => [['value' => 'x *** y']], 'metadata' => ['***' => 'harmless']],
            $redactor->redact(['fields' => [['value' => 'x s3cr3t-value y']], 'metadata' => ['s3cr3t-value' => 'harmless']])
        );
    }

    /**
     * Values shorter than the minimum length and non-string values are not treated as secrets.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testIgnoresShortAndNonStringSecrets(): void
    {
        $redactor = new ArgumentRedactor(['short', '   ', '', null, 12345678, ['array-value']]);

        $this->assertSame(
            'short text with 12345678 and array-value',
            $redactor->redact('short text with 12345678 and array-value')
        );
    }

    /**
     * The token, its decoded form and the configuration secrets are redacted; other configuration values are not.
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

        $token    = base64_encode('sha256:42:0123456789abcdef');
        $redactor = ArgumentRedactor::fromConfiguration($config, $token);

        $this->assertSame('***', $redactor->redact($token));
        $this->assertSame('***', $redactor->redact('sha256:42:0123456789abcdef'));
        $this->assertSame('***', $redactor->redact('site-secret-word'));
        $this->assertSame('***', $redactor->redact('db-password-1'));
        $this->assertSame('***', $redactor->redact('smtp-password-1'));
        $this->assertSame('My public site name', $redactor->redact('My public site name'));
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
        $redactor = ArgumentRedactor::fromConfiguration(new Registry(), null);

        $this->assertSame(['title' => 'Anything'], $redactor->redact(['title' => 'Anything']));
    }

    /**
     * Unreasonably deep structures are masked rather than partially redacted.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testMasksExcessivelyDeepStructures(): void
    {
        $value = 's3cr3t-value';

        for ($i = 0; $i < 100; $i++) {
            $value = [$value];
        }

        $this->assertStringNotContainsString(
            's3cr3t-value',
            json_encode((new ArgumentRedactor(['s3cr3t-value']))->redact($value))
        );
    }
}
