<?php

/**
 * @package     Joomla.Platform
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\WebService\Internal;

/**
 * Signals that an internally dispatched API controller requested application termination.
 *
 * @since  __DEPLOY_VERSION__
 */
final class InternalApiApplicationClosed extends \RuntimeException
{
}
