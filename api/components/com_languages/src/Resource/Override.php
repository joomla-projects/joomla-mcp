<?php

/**
 * @package     Joomla.API
 * @subpackage  com_languages
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Languages\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Resource;

class Override extends Resource
{
    public function __construct(
        #[Description("Language constant key")]
        public string $id,
        #[Description("Overridden translation value")]
        public string $value,
    )
    {
    }
}
