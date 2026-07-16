<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Auth;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\OAuth\ResourceServer\AccessTokenPrincipal;
use Joomla\CMS\OAuth\ResourceServer\AccessTokenValidatorInterface;
use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;
use Joomla\CMS\OAuth\ResourceServer\TokenValidationException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Rsa\Sha384;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

/**
 * Validates RFC 9068-style JWT access tokens against a trusted issuer and JWKS endpoint.
 *
 * @since  __DEPLOY_VERSION__
 */
final class JwtAccessTokenValidator implements AccessTokenValidatorInterface
{
    /**
     * @param  list<string>  $allowedAlgorithms  Permitted JWT signing algorithms.
     * @param  list<string>  $allowedTypes       Permitted JWT type header values.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly string $issuer,
        private readonly JwksProviderInterface $jwksProvider,
        private readonly array $allowedAlgorithms = ['RS256'],
        private readonly array $allowedTypes = ['at+jwt'],
        private readonly int $clockSkew = 60,
    ) {
        if (!filter_var($issuer, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The trusted OAuth issuer must be an absolute URI.');
        }
    }

    /**
     * @inheritDoc
     */
    public function validate(
        string $accessToken,
        ResourceIdentifier $expectedResource,
    ): AccessTokenPrincipal {
        try {
            [$header] = $this->decodeTokenParts($accessToken);
            $algorithm = $this->requiredString($header, 'alg');
            $type      = strtolower($this->requiredString($header, 'typ'));

            if (!\in_array($algorithm, $this->allowedAlgorithms, true)) {
                throw new TokenValidationException('The access token uses an unsupported signing algorithm.');
            }

            $allowedTypes = array_map('strtolower', $this->allowedTypes);

            if (!\in_array($type, $allowedTypes, true)) {
                throw new TokenValidationException('The supplied token is not an accepted OAuth access token type.');
            }

            $keyId         = isset($header['kid']) && \is_string($header['kid']) ? $header['kid'] : null;
            $jwk           = $this->jwksProvider->getKey($keyId);
            $declaredAlg   = $jwk['alg'] ?? null;

            if (\is_string($declaredAlg) && $declaredAlg !== $algorithm) {
                throw new TokenValidationException('The signing key is not intended for the access-token algorithm.');
            }

            $signer        = $this->signer($algorithm);
            $configuration = Configuration::forAsymmetricSigner(
                $signer,
                InMemory::plainText($this->rsaPublicKey($jwk)),
                InMemory::plainText($this->rsaPublicKey($jwk)),
            );
            $token = $configuration->parser()->parse($accessToken);

            if (!$token instanceof UnencryptedToken) {
                throw new TokenValidationException('The access token format is not supported.');
            }

            $constraints = [
                new SignedWith($signer, $configuration->verificationKey()),
                new IssuedBy($this->issuer),
                new PermittedFor((string) $expectedResource),
                new StrictValidAt(
                    new SystemClock(new \DateTimeZone('UTC')),
                    new \DateInterval('PT' . max(0, $this->clockSkew) . 'S'),
                ),
            ];

            $configuration->validator()->assert($token, ...$constraints);

            $claims    = $token->claims();
            $issuer    = $this->claimString($claims->all(), 'iss');
            $subject   = $this->claimString($claims->all(), 'sub');
            $clientId  = $this->claimString($claims->all(), 'client_id');
            $audiences = $this->audiences($claims->get('aud'));
            $scopes    = $this->scopes($claims->get('scope', ''));
            $expiresAt = $claims->get('exp');

            if (!$expiresAt instanceof \DateTimeImmutable) {
                throw new TokenValidationException('The access token does not contain a valid expiry time.');
            }

            $issuedAt = $claims->get('iat', null);

            if ($issuedAt !== null && !$issuedAt instanceof \DateTimeImmutable) {
                throw new TokenValidationException('The access token contains an invalid issue time.');
            }

            $authenticatedAt = $claims->get('auth_time', null);

            if ($authenticatedAt !== null && !$authenticatedAt instanceof \DateTimeImmutable) {
                $authenticatedAt = null;
            }

            $amr = $claims->get('amr', []);
            $amr = \is_array($amr) ? array_values(array_filter($amr, 'is_string')) : [];
            $jti = $claims->get('jti', null);

            return new AccessTokenPrincipal(
                issuer: $issuer,
                subject: $subject,
                clientId: $clientId,
                audiences: $audiences,
                scopes: $scopes,
                issuedAt: $issuedAt,
                expiresAt: $expiresAt,
                tokenId: \is_string($jti) && $jti !== '' ? $jti : null,
                authenticatedAt: $authenticatedAt,
                authenticationMethods: $amr,
            );
        } catch (TokenValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new TokenValidationException('The OAuth access token is invalid.', 0, $exception);
        }
    }

    /**
     * Decodes the untrusted JWT header before signature validation.
     *
     * @return  array{0: array<string, mixed>, 1: array<string, mixed>}
     *
     * @since  __DEPLOY_VERSION__
     */
    private function decodeTokenParts(string $token): array
    {
        $parts = explode('.', $token);

        if (\count($parts) !== 3) {
            throw new TokenValidationException('The access token is not a compact JWT.');
        }

        try {
            $header  = json_decode($this->base64UrlDecode($parts[0]), true, 32, JSON_THROW_ON_ERROR);
            $payload = json_decode($this->base64UrlDecode($parts[1]), true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TokenValidationException('The access token contains invalid JSON.', 0, $exception);
        }

        if (!\is_array($header) || !\is_array($payload)) {
            throw new TokenValidationException('The access token contains invalid JWT objects.');
        }

        return [$header, $payload];
    }

    /**
     * Returns a required string from an untrusted JWT header.
     *
     * @param  array<string, mixed>  $values  Header values.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function requiredString(array $values, string $name): string
    {
        $value = $values[$name] ?? null;

        if (!\is_string($value) || $value === '') {
            throw new TokenValidationException(sprintf('The access token is missing the %s header.', $name));
        }

        return $value;
    }

    /**
     * Returns a required string claim.
     *
     * @param  array<string, mixed>  $claims  Token claims.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function claimString(array $claims, string $name): string
    {
        $value = $claims[$name] ?? null;

        if (!\is_string($value) || $value === '') {
            throw new TokenValidationException(sprintf('The access token is missing the %s claim.', $name));
        }

        return $value;
    }

    /**
     * Normalises the OAuth audience claim.
     *
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function audiences(mixed $audience): array
    {
        if (\is_string($audience) && $audience !== '') {
            return [$audience];
        }

        if (\is_array($audience)) {
            return array_values(
                array_filter($audience, static fn ($value): bool => \is_string($value) && $value !== ''),
            );
        }

        throw new TokenValidationException('The access token does not contain a valid audience claim.');
    }

    /**
     * Normalises an OAuth scope claim.
     *
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function scopes(mixed $scope): array
    {
        if (\is_string($scope)) {
            return array_values(array_unique(array_filter(preg_split('/\s+/', trim($scope)) ?: [])));
        }

        if (\is_array($scope)) {
            return array_values(array_unique(array_filter($scope, 'is_string')));
        }

        throw new TokenValidationException('The access token contains an invalid scope claim.');
    }

    /**
     * Creates the permitted RSA signer.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function signer(string $algorithm): Signer
    {
        return match ($algorithm) {
            'RS256' => new Sha256(),
            'RS384' => new Sha384(),
            'RS512' => new Sha512(),
            default => throw new TokenValidationException('The access-token signing algorithm is not supported.'),
        };
    }

    /**
     * Converts an RSA JSON Web Key to a PEM public key.
     *
     * @param  array<string, mixed>  $jwk  JSON Web Key.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function rsaPublicKey(array $jwk): string
    {
        $modulus  = $this->base64UrlDecode($this->requiredString($jwk, 'n'));
        $exponent = $this->base64UrlDecode($this->requiredString($jwk, 'e'));
        $rsaKey   = $this->asn1Sequence($this->asn1Integer($modulus) . $this->asn1Integer($exponent));
        $algorithmIdentifier = hex2bin('300d06092a864886f70d0101010500');

        if ($algorithmIdentifier === false) {
            throw new \LogicException('The RSA algorithm identifier could not be created.');
        }

        $subjectPublicKeyInfo = $this->asn1Sequence(
            $algorithmIdentifier . "\x03" . $this->asn1Length(\strlen($rsaKey) + 1) . "\x00" . $rsaKey,
        );

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Encodes an ASN.1 sequence.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function asn1Sequence(string $value): string
    {
        return "\x30" . $this->asn1Length(\strlen($value)) . $value;
    }

    /**
     * Encodes an unsigned ASN.1 integer.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\x00");
        $value = $value === '' ? "\x00" : $value;

        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->asn1Length(\strlen($value)) . $value;
    }

    /**
     * Encodes an ASN.1 length.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = '';

        while ($length > 0) {
            $encoded = chr($length & 0xff) . $encoded;
            $length >>= 8;
        }

        return chr(0x80 | \strlen($encoded)) . $encoded;
    }

    /**
     * Decodes base64url data.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function base64UrlDecode(string $value): string
    {
        $padding = (4 - \strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value . str_repeat('=', $padding), '-_', '+/'), true);

        if ($decoded === false) {
            throw new TokenValidationException('The access token contains invalid base64url data.');
        }

        return $decoded;
    }
}
