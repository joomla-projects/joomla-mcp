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

use Joomla\CMS\Http\HttpFactory;

/**
 * Retrieves and caches a JSON Web Key Set from a configured HTTPS endpoint.
 *
 * @since  __DEPLOY_VERSION__
 */
final class RemoteJwksProvider implements JwksProviderInterface
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly string $jwksUri,
        private readonly int $cacheLifetime = 3600,
        private readonly int $timeout = 10,
    ) {
        $parts = parse_url($jwksUri);

        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException('The JWKS URI must be an absolute URI.');
        }

        if (strtolower((string) $parts['scheme']) !== 'https' && !$this->isLoopbackHost((string) $parts['host'])) {
            throw new \InvalidArgumentException('The JWKS URI must use HTTPS.');
        }
    }

    /**
     * @inheritDoc
     */
    public function getKey(?string $keyId): array
    {
        $document = $this->loadDocument();
        $keys     = $document['keys'] ?? null;

        if (!\is_array($keys) || $keys === []) {
            throw new \RuntimeException('The configured JWKS document does not contain any keys.');
        }

        $candidates = array_values(
            array_filter(
                $keys,
                static fn ($key): bool => \is_array($key)
                    && (($key['use'] ?? 'sig') === 'sig')
                    && (($key['kty'] ?? null) === 'RSA'),
            ),
        );

        if ($keyId !== null && $keyId !== '') {
            foreach ($candidates as $candidate) {
                if (($candidate['kid'] ?? null) === $keyId) {
                    return $candidate;
                }
            }

            // A key rotation may have happened before the local cache expired.
            $document   = $this->fetchDocument();
            $candidates = array_values(
                array_filter(
                    $document['keys'] ?? [],
                    static fn ($key): bool => \is_array($key)
                        && (($key['use'] ?? 'sig') === 'sig')
                        && (($key['kty'] ?? null) === 'RSA'),
                ),
            );

            foreach ($candidates as $candidate) {
                if (($candidate['kid'] ?? null) === $keyId) {
                    return $candidate;
                }
            }

            throw new \RuntimeException('No signing key matches the access token key identifier.');
        }

        if (\count($candidates) !== 1) {
            throw new \RuntimeException('The access token must identify one of the available signing keys.');
        }

        return $candidates[0];
    }

    /**
     * Loads the JWKS document from the bounded cache or the remote endpoint.
     *
     * @return  array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function loadDocument(): array
    {
        $cacheFile = $this->cacheFile();

        if (is_file($cacheFile) && filemtime($cacheFile) >= time() - $this->cacheLifetime) {
            $contents = file_get_contents($cacheFile);

            if (\is_string($contents)) {
                try {
                    $document = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

                    if (\is_array($document)) {
                        return $document;
                    }
                } catch (\JsonException) {
                    // Ignore a corrupt cache entry and fetch a fresh document.
                }
            }
        }

        return $this->fetchDocument();
    }

    /**
     * Fetches and stores the configured JWKS document.
     *
     * @return  array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    private function fetchDocument(): array
    {
        $response = HttpFactory::getHttp()->get(
            $this->jwksUri,
            ['Accept' => 'application/json'],
            $this->timeout,
        );

        if ((int) $response->code !== 200) {
            throw new \RuntimeException('The Authorization Server JWKS endpoint returned an unexpected status.');
        }

        try {
            $document = json_decode((string) $response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('The Authorization Server returned an invalid JWKS document.', 0, $exception);
        }

        if (!\is_array($document) || !\is_array($document['keys'] ?? null)) {
            throw new \RuntimeException('The Authorization Server returned an invalid JWKS document.');
        }

        $cacheFile = $this->cacheFile();
        $directory = \dirname($cacheFile);

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('The JWKS cache directory could not be created.');
        }

        $encoded = json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $tmpFile = $cacheFile . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (file_put_contents($tmpFile, $encoded, LOCK_EX) === false || !rename($tmpFile, $cacheFile)) {
            @unlink($tmpFile);
            throw new \RuntimeException('The JWKS document could not be cached.');
        }

        @chmod($cacheFile, 0600);

        return $document;
    }

    /**
     * Returns the cache file used for this JWKS URI.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function cacheFile(): string
    {
        return JPATH_CACHE . '/com_mcp/jwks-' . hash('sha256', $this->jwksUri) . '.json';
    }

    /**
     * Checks whether the host is permitted for local development.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function isLoopbackHost(string $host): bool
    {
        return \in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    }
}
