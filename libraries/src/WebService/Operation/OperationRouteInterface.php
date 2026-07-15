<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Operation;

/**
 * Identifies router routes that carry their canonical operation definition.
 *
 * @since  __DEPLOY_VERSION__
 */
interface OperationRouteInterface
{
    /**
     * Returns the operation represented by this route.
     *
     * @return  OperationDefinition
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getOperation(): OperationDefinition;
}
