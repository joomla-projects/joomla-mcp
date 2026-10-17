<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Plugin\MCP\Joomla\Prompt;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\Plugin\MCP\Joomla\Prompt\SiteHealthCheck;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for the siteHealthCheck prompt.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class SiteHealthCheckTest extends UnitTestCase
{
    /**
     * The language object before the test
     *
     * @var    ?Language
     * @since  __DEPLOY_VERSION__
     */
    private ?Language $previousLanguage;

    /**
     * Use a language stub that returns "%s site" for the result description and the key otherwise
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function setUp(): void
    {
        $this->previousLanguage = Factory::$language;

        $language = $this->createStub(Language::class);
        $language->method('_')->willReturnCallback(
            static fn (string $key) => $key === 'PLG_MCP_JOOMLA_SITE_HEALTH_CHECK_RESULT_DESC' ? '%s site' : $key
        );

        Factory::$language = $language;
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function tearDown(): void
    {
        Factory::$language = $this->previousLanguage;
    }

    /**
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function environmentProvider(): array
    {
        return [
            'missing defaults to production' => [[], 'production'],
            'development'                    => [['environment' => 'development'], 'development'],
            'case and whitespace ignored'    => [['environment' => ' STAGING '], 'staging'],
            'unknown falls back to strict'   => [['environment' => 'foo'], 'production'],
        ];
    }

    /**
     * @param   array   $arguments  The prompt arguments
     * @param   string  $expected   The expected environment
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[DataProvider('environmentProvider')]
    public function testEnvironmentIsNormalised(array $arguments, string $expected): void
    {
        $result   = (new SiteHealthCheck())->getMessages($arguments);
        $messages = $result->getMessages();

        $this->assertCount(1, $messages);
        $this->assertSame('user', $messages[0]->getRole());
        $this->assertStringContainsString('The site is a ' . $expected . ' environment.', $messages[0]->toArray()['content']['text']);
        $this->assertSame($expected . ' site', $result->getDescription());
    }

    /**
     * The prompt relies on the read-only tools and must forbid changes.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testInstructionsUseReadOnlyTools(): void
    {
        $text = (new SiteHealthCheck())->getMessages([])->getMessages()[0]->toArray()['content']['text'];

        $this->assertStringContainsString('getSysInfo', $text);
        $this->assertStringContainsString('getApplicationConfig', $text);
        $this->assertStringContainsString('Do not change anything', $text);
    }

    /**
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testEnvironmentArgumentIsOptional(): void
    {
        $arguments = (new SiteHealthCheck())->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertSame('environment', $arguments[0]['name']);
        $this->assertFalse($arguments[0]['required']);
    }
}
