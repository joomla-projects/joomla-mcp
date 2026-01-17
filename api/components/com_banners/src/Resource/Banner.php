<?php

/**
 * @package     Joomla.API
 * @subpackage  com_banners
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Banners\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Example;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class Banner extends Resource
{
    public function __construct(
        #[Guarded]
        public int $id,
        #[Description("Category of banner, see category resources for extension com_banners")]
        public int $cid,
        #[Description("Use 0 for image, 1 for custom code")]
        public int $type,
        public string $typeAlias,
        #[Guarded]
        public int $imptotal,
        #[Guarded]
        public int $impmade,
        #[Guarded]
        public int $clicks,
        public int $sticky,
        public int $ordering,
        public string $clickurl,
        public string $name,
        #[Example("Some description of this banner")]
        public string $description,
        public string $custombannercode,
        public string $alias,
        public string $metakey,
        public string $params,
        #[Description("use 1 for published, 0 for unpublished")]
        public int $state,
        #[Description("use * for all languages")]
        public string $language,

    )
    {
    }
}
