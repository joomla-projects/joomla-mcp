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
 * Standard resource operation types
 *
 * @since  __DEPLOY_VERSION__
 */
enum OperationType
{
    case READ;
    case LIST;
    case CREATE;
    case UPDATE;
}
