<?php

/**
 * @package     Joomla.API
 * @subpackage  com_templates
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Templates\Api\Definition\Resource;

use Joomla\CMS\WebService\Definition\Attribute\Property\Description;
use Joomla\CMS\WebService\Definition\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Definition\Resource\BaseResource;

class Style extends BaseResource
{
    public function __construct(
        #[Guarded]
        public int $id,
        #[Guarded]
        public string $template,
        public int $client_id,
        #[Description("use 1 for default home template, 0 for non-default")]
        public int $home,
        public string $title,
        public string $params,
        public string $xml,
    ) {
    }
}
