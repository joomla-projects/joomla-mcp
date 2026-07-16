<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\OAuth\ResourceServer\ResourceIdentifier;

/**
 * Builds RFC 9728-style metadata for the MCP protected resource.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ProtectedResourceMetadataProvider
{
    /**
     * @param  list<string>  $authorizationServers  Trusted Authorization Server issuers.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private ResourceIdentifier $resource,
        private array $authorizationServers,
        private string $metadataUri,
        private ?string $documentationUri = null,
    ) {
    }

    /**
     * @param  list<string>  $scopes  Supported resource scopes.
     *
     * @return  array<string, mixed>
     *
     * @since  __DEPLOY_VERSION__
     */
    public function create(array $scopes): array
    {
        $metadata = [
            'resource'                 => (string) $this->resource,
            'authorization_servers'    => array_values($this->authorizationServers),
            'scopes_supported'         => array_values(array_unique($scopes)),
            'bearer_methods_supported' => ['header'],
        ];

        if ($this->documentationUri !== null && $this->documentationUri !== '') {
            $metadata['resource_documentation'] = $this->documentationUri;
        }

        return $metadata;
    }

    /**
     * Returns the advertised metadata URI.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getMetadataUri(): string
    {
        return $this->metadataUri;
    }

    /**
     * Returns the protected resource identifier.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getResource(): ResourceIdentifier
    {
        return $this->resource;
    }
}
