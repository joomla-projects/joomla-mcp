<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Internal;

use Joomla\CMS\WebService\Operation\OperationDefinition;
use Joomla\CMS\WebService\Operation\OperationInput;

/**
 * Dispatches a compiled operation through Joomla's existing component dispatcher.
 *
 * @since  __DEPLOY_VERSION__
 */
interface InternalApiDispatcherInterface
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function dispatch(OperationDefinition $operation, OperationInput $input): InternalApiResponse;
}
