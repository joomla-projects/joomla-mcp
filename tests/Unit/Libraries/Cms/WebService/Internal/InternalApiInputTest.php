<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  WebService
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\WebService\Internal;

use Joomla\CMS\WebService\Internal\InternalApiInput;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joomla\CMS\WebService\Internal\InternalApiInput
 */
final class InternalApiInputTest extends TestCase
{
    public function testItProvidesAnIsolatedJsonRequestView(): void
    {
        $input = new InternalApiInput(
            [
                'option' => 'com_content',
                'task'   => 'articles.edit',
                'id'     => 42,
                'data'   => ['title' => 'Changed'],
                'title'  => 'Changed',
            ],
            ['title'  => 'Changed'],
            ['filter' => ['state' => 1]],
            'PATCH',
        );

        self::assertSame('PATCH', $input->getMethod());
        self::assertSame('{"title":"Changed"}', $input->getRaw());
        self::assertSame($input, $input->json);
        self::assertSame('Changed', $input->json->getString('title'));
        self::assertSame(42, $input->getInt('id'));
        self::assertSame(['state' => 1], $input->get->get('filter', [], 'array'));
    }
}
