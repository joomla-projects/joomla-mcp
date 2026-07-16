<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  WebService.Internal
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\CMS\WebService\Internal;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\User\User;
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
     * Dispatches an operation under the explicitly supplied Joomla identity.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function dispatch(
        OperationDefinition $operation,
        OperationInput $input,
        User $identity,
    ): InternalApiResponse;
}
