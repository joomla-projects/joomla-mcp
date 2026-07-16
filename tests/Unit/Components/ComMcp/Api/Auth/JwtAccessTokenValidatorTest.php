<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Tests\Unit\Components\ComMcp\Api\Auth;

use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;
use Joomla\CMS\OAuth\ResourceServer\TokenValidationException;
use Joomla\Component\MCP\Api\Auth\JwksProviderInterface;
use Joomla\Component\MCP\Api\Auth\JwtAccessTokenValidator;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;

/**
 * Tests JWT access-token validation.
 *
 * @since  __DEPLOY_VERSION__
 */
final class JwtAccessTokenValidatorTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;
    private array $jwk;

    protected function setUp(): void
    {
        $key = openssl_pkey_new(
            [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ],
        );

        self::assertNotFalse($key);
        self::assertTrue(openssl_pkey_export($key, $this->privateKey));

        $details = openssl_pkey_get_details($key);
        self::assertIsArray($details);
        $this->publicKey = $details['key'];
        $this->jwk       = [
            'kty' => 'RSA',
            'use' => 'sig',
            'kid' => 'test-key',
            'alg' => 'RS256',
            'n'   => $this->base64UrlEncode($details['rsa']['n']),
            'e'   => $this->base64UrlEncode($details['rsa']['e']),
        ];
    }

    public function testValidatesTokenForExpectedResource(): void
    {
        $resource  = 'https://site.example/api/index.php/v1/mcp';
        $validator = $this->validator();
        $principal = $validator->validate($this->token($resource), new ResourceIdentifier($resource));

        self::assertSame('https://issuer.example', $principal->issuer);
        self::assertSame('42', $principal->subject);
        self::assertSame('client-one', $principal->clientId);
        self::assertSame(['mcp:use', 'content.articles:read'], $principal->scopes);
    }

    public function testRejectsWrongAudience(): void
    {
        $this->expectException(TokenValidationException::class);

        $this->validator()->validate(
            $this->token('https://another.example/api/index.php/v1/mcp'),
            new ResourceIdentifier('https://site.example/api/index.php/v1/mcp'),
        );
    }

    public function testRejectsIdTokenType(): void
    {
        $this->expectException(TokenValidationException::class);

        $this->validator()->validate(
            $this->token('https://site.example/api/index.php/v1/mcp', 'JWT'),
            new ResourceIdentifier('https://site.example/api/index.php/v1/mcp'),
        );
    }

    private function validator(): JwtAccessTokenValidator
    {
        $provider = new class ($this->jwk) implements JwksProviderInterface {
            public function __construct(private readonly array $jwk)
            {
            }

            public function getKey(?string $keyId): array
            {
                return $this->jwk;
            }
        };

        return new JwtAccessTokenValidator('https://issuer.example', $provider);
    }

    private function token(string $audience, string $type = 'at+jwt'): string
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey),
            InMemory::plainText($this->publicKey),
        );
        $now = new \DateTimeImmutable();

        return $configuration->builder()
            ->withHeader('typ', $type)
            ->withHeader('kid', 'test-key')
            ->issuedBy('https://issuer.example')
            ->permittedFor($audience)
            ->relatedTo('42')
            ->identifiedBy('token-id')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+5 minutes'))
            ->withClaim('client_id', 'client-one')
            ->withClaim('scope', 'mcp:use content.articles:read')
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
