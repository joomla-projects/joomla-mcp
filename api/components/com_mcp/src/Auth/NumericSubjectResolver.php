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
 * Deployment adapter for Authorization Servers whose subject is the Joomla user identifier.
 *
 * This adapter is deliberately separate from the OAuth Resource Server contract. Installations
 * using opaque subjects can replace it with another JoomlaSubjectResolverInterface service.
 *
 * @since  __DEPLOY_VERSION__
 */
final class NumericSubjectResolver implements JoomlaSubjectResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(string $issuer, string $subject): User
    {
        if (!ctype_digit($subject) || (int) $subject < 1) {
            throw new SubjectResolutionException('The OAuth subject is not mapped to a Joomla user.');
        }

        $user = new User((int) $subject);

        if ($user->id < 1) {
            throw new SubjectResolutionException('The OAuth subject is not mapped to a Joomla user.');
        }

        if ((int) $user->block === 1) {
            throw new SubjectResolutionException('The Joomla user account is blocked.');
        }

        if (!empty(trim((string) $user->activation))) {
            throw new SubjectResolutionException('The Joomla user account has not been activated.');
        }

        if ((int) ($user->requireReset ?? 0) === 1) {
            throw new SubjectResolutionException('The Joomla user account requires a password reset.');
        }

        return $user;
    }
}
