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

use Joomla\CMS\User\User;

/**
 * Maps an OAuth subject to a Joomla user.
 *
 * @since  __DEPLOY_VERSION__
 */
interface JoomlaSubjectResolverInterface
{
    /**
     * Resolves an issuer and subject to an active Joomla user.
     *
     * @throws  SubjectResolutionException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function resolve(string $issuer, string $subject): User;
}
