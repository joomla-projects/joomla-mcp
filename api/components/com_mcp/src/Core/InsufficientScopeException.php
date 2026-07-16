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

/**
 * Raised when an OAuth principal lacks one or more required scopes.
 *
 * @since  __DEPLOY_VERSION__
 */
final class InsufficientScopeException extends \RuntimeException
{
    /**
     * @param  list<string>  $requiredScopes  Missing or required scopes.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(public readonly array $requiredScopes)
    {
        parent::__construct('The access token does not contain the required OAuth scopes.');
    }
}
