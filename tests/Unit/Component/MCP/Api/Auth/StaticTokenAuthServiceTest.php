<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Auth;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Component\MCP\Administrator\Helper\TokenHelper;
use Joomla\Component\MCP\Administrator\Model\ClientModel;
use Joomla\Component\MCP\Api\Auth\StaticTokenAuthService;
use Joomla\Database\DatabaseDriver;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Exercise the renamed model's real HMAC authentication through the API service.
 *
 * @since  __DEPLOY_VERSION__
 */
class StaticTokenAuthServiceTest extends UnitTestCase
{
    private string $secret = 'mcp-regression-site-secret';
    private mixed $previousApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousApplication = Factory::$application;
        $app                       = $this->createStub(AdministratorApplication::class);
        $app->method('get')->willReturnCallback(fn ($key, $default = null) => $key === 'secret' ? $this->secret : $default);
        Factory::$application = $app;
    }

    protected function tearDown(): void
    {
        Factory::$application = $this->previousApplication;
        parent::tearDown();
    }

    public static function algorithms(): array
    {
        return [['sha256'], ['sha512']];
    }

    #[DataProvider('algorithms')]
    public function testDerivedTokenReturnsIdentityWithoutExposingSeed(string $algo): void
    {
        $seed     = TokenHelper::generateSeed();
        $token    = TokenHelper::buildToken(42, $seed, $algo);
        $model    = $this->modelWithRecord($seed);
        $identity = (new StaticTokenAuthService($model))->validateToken($token);

        $this->assertNotNull($identity);
        $this->assertSame($token, $identity->token);
        $this->assertNotSame($seed, $identity->token);
        $this->assertSame(7, $identity->userid);
        $this->assertSame('Desktop client', $identity->client_name);
    }

    public function testTamperedTokenIsRejected(): void
    {
        $seed  = TokenHelper::generateSeed();
        $token = base64_encode('sha256:42:' . str_repeat('0', 64));
        $this->assertNull((new StaticTokenAuthService($this->modelWithRecord($seed)))->validateToken($token));
    }

    public function testRegeneratedSeedRejectsPreviousToken(): void
    {
        $token = TokenHelper::buildToken(42, TokenHelper::generateSeed());
        $this->assertNull((new StaticTokenAuthService($this->modelWithRecord(TokenHelper::generateSeed())))->validateToken($token));
    }

    public function testChangedSiteSecretRejectsPreviousToken(): void
    {
        $seed         = TokenHelper::generateSeed();
        $token        = TokenHelper::buildToken(42, $seed);
        $this->secret = 'different-site-secret';
        $this->assertNull((new StaticTokenAuthService($this->modelWithRecord($seed)))->validateToken($token));
    }

    public function testEmptySiteSecretRejectsToken(): void
    {
        $seed         = TokenHelper::generateSeed();
        $token        = TokenHelper::buildToken(42, $seed);
        $this->secret = '';
        $this->assertNull((new StaticTokenAuthService($this->modelWithRecord($seed)))->validateToken($token));
    }

    public function testUnknownOrUnpublishedClientIsRejected(): void
    {
        $token = TokenHelper::buildToken(42, TokenHelper::generateSeed());
        $this->assertNull((new StaticTokenAuthService($this->modelWithRecord(null)))->validateToken($token));
    }

    public static function malformedTokens(): array
    {
        return [
            [null], [''], ['not-base64!'], [base64_encode('sha256:0:hash')],
            [base64_encode('md5:42:hash')], [base64_encode('sha256:42:')],
            [base64_encode('sha256:abc:hash')], [base64_encode('sha256:42')],
            [base64_encode(str_repeat('s', 32))],
        ];
    }

    #[DataProvider('malformedTokens')]
    public function testMalformedTokensAndStoredSeedsNeverQueryDatabase(?string $token): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('createQuery');
        $model = new ClientModel(['ignore_request' => true]);
        $model->setDatabase($db);
        $this->assertNull((new StaticTokenAuthService($model))->validateToken($token));
    }

    /**
     * Mock only persistence: parsing, HMAC verification and API mapping are real.
     */
    private function modelWithRecord(?string $seed): ClientModel
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('quoteName')->willReturnArgument(0);
        $query = new \Joomla\Database\Mysqli\MysqliQuery($db);
        $db->expects($this->once())->method('createQuery')->willReturn($query);
        $db->expects($this->once())->method('setQuery')->willReturnCallback(function ($actual) use ($db) {
            $this->assertStringContainsString('id = :id', (string) $actual);
            $this->assertStringContainsString('state = 1', (string) $actual);
            $this->assertSame(42, $actual->getBounded()[':id']->value);
            return $db;
        });
        $db->expects($this->once())->method('loadAssoc')->willReturn($seed === null ? null : [
            'id' => 42, 'state' => 1, 'client_token' => $seed, 'user_id' => 7, 'client_name' => 'Desktop client',
        ]);
        $model = new ClientModel(['ignore_request' => true]);
        $model->setDatabase($db);
        return $model;
    }
}
