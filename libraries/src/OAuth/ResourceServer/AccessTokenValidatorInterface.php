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
 * Validates OAuth access tokens for a protected resource.
 *
 * @since  __DEPLOY_VERSION__
 */
interface AccessTokenValidatorInterface
{
    /**
     * Validates an access token for the expected resource.
     *
     * @throws  TokenValidationException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function validate(
        string $accessToken,
        ResourceIdentifier $expectedResource,
    ): AccessTokenPrincipal;
}
