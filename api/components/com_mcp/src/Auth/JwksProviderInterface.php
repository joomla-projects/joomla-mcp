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

/**
 * Provides JSON Web Keys used to validate access-token signatures.
 *
 * @since  __DEPLOY_VERSION__
 */
interface JwksProviderInterface
{
    /**
     * Returns a JSON Web Key for the requested key identifier.
     *
     * @return  array<string, mixed>
     *
     * @throws  \RuntimeException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getKey(?string $keyId): array;
}
