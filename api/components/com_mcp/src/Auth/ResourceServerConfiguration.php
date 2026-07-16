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

use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;
use Joomla\Registry\Registry;

/**
 * Validated configuration of the MCP OAuth Resource Server.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ResourceServerConfiguration
{
    /**
     * @param  list<string>  $allowedAlgorithms  Accepted JWT signing algorithms.
     * @param  list<string>  $allowedTypes       Accepted JWT type values.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        public ResourceIdentifier $resource,
        public string $issuer,
        public string $jwksUri,
        public string $metadataUri,
        public string $baseScope = 'mcp:use',
        public array $allowedAlgorithms = ['RS256'],
        public array $allowedTypes = ['at+jwt'],
        public int $clockSkew = 60,
        public int $jwksCacheLifetime = 3600,
        public ?string $documentationUri = null,
    ) {
    }

    /**
     * Creates a configuration from component parameters.
     *
     * @throws  \InvalidArgumentException
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function fromRegistry(Registry $params): self
    {
        $resourceUri = trim((string) $params->get('oauth_resource_uri', ''));
        $issuer      = trim((string) $params->get('oauth_issuer', ''));
        $jwksUri     = trim((string) $params->get('oauth_jwks_uri', ''));

        if ($resourceUri === '' || $issuer === '' || $jwksUri === '') {
            throw new \InvalidArgumentException(
                'The MCP OAuth resource URI, issuer and JWKS URI must be configured.',
            );
        }

        $resource    = new ResourceIdentifier($resourceUri);
        $metadataUri = trim((string) $params->get('oauth_resource_metadata_uri', ''));

        if ($metadataUri === '') {
            $metadataUri = rtrim($resourceUri, '/') . '/oauth-protected-resource';
        }

        // Apply the same URI validation rules to the advertised metadata endpoint.
        new ResourceIdentifier($metadataUri);

        $algorithms = self::csv((string) $params->get('oauth_allowed_algorithms', 'RS256'));
        $types      = self::csv((string) $params->get('oauth_allowed_token_types', 'at+jwt'));

        return new self(
            resource: $resource,
            issuer: $issuer,
            jwksUri: $jwksUri,
            metadataUri: $metadataUri,
            baseScope: trim((string) $params->get('oauth_base_scope', 'mcp:use')) ?: 'mcp:use',
            allowedAlgorithms: $algorithms === [] ? ['RS256'] : $algorithms,
            allowedTypes: $types === [] ? ['at+jwt'] : $types,
            clockSkew: max(0, (int) $params->get('oauth_clock_skew', 60)),
            jwksCacheLifetime: max(60, (int) $params->get('oauth_jwks_cache_lifetime', 3600)),
            documentationUri: trim((string) $params->get('oauth_resource_documentation_uri', '')) ?: null,
        );
    }

    /**
     * @return  list<string>
     *
     * @since  __DEPLOY_VERSION__
     */
    private static function csv(string $value): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('trim', explode(',', $value)),
                    static fn (string $item): bool => $item !== '',
                ),
            ),
        );
    }
}
