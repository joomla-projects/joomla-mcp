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
 * Raised when an OAuth access token cannot be accepted.
 *
 * @since  __DEPLOY_VERSION__
 */
final class TokenValidationException extends \RuntimeException
{
}
