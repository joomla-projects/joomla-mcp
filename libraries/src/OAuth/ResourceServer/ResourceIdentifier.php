<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  OAuth.ResourceServer
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\CMS\OAuth\ResourceServer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Canonical identifier of an OAuth-protected resource.
 *
 * @since  __DEPLOY_VERSION__
 */
final readonly class ResourceIdentifier
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public string $uri)
    {
        $parts = parse_url($uri);

        if (!\is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new \InvalidArgumentException('The OAuth resource identifier must be an absolute URI.');
        }

        if (strtolower((string) $parts['scheme']) !== 'https' && !$this->isLoopbackHost((string) $parts['host'])) {
            throw new \InvalidArgumentException('The OAuth resource identifier must use HTTPS.');
        }

        if (isset($parts['fragment'])) {
            throw new \InvalidArgumentException('The OAuth resource identifier must not contain a fragment.');
        }
    }

    /**
     * Returns the identifier as a string.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __toString(): string
    {
        return $this->uri;
    }

    /**
     * Checks whether the host is a loopback host permitted for local development.
     *
     * @since  __DEPLOY_VERSION__
     */
    private function isLoopbackHost(string $host): bool
    {
        return \in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
    }
}
