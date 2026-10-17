<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Administrator\Model;

use Joomla\CMS\Form\Form;
use Joomla\CMS\User\User;
use Joomla\Component\MCP\Administrator\Model\McpModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests initial selection mode when creating and editing MCP clients.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpModelTest extends UnitTestCase
{
    /**
     * New clients start restricted while existing clients retain their current selection mode.
     *
     * @param   array   $data      The client data loaded into the form.
     * @param   string  $expected  The selection mode shown to the administrator.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[DataProvider('clientSettings')]
    public function testFormSelectsTheAppropriateMode(array $data, string $expected): void
    {
        $form = new Form('com_mcp.mcp', ['control' => 'jform']);
        $form->setDatabase($this->createStub(DatabaseInterface::class));
        $form->setCurrentUser(new User());
        $form->loadFile(JPATH_ADMINISTRATOR . '/components/com_mcp/forms/mcp.xml');
        $form->bind($data);
        $model = $this->getMockBuilder(McpModel::class)->disableOriginalConstructor()->onlyMethods(['loadForm'])->getMock();
        $model->expects($this->once())->method('loadForm')->willReturn($form);

        $this->assertSame($expected, $model->getForm()->getField('tools_mode', 'params')->value);
    }

    /**
     * Cover new clients, legacy clients, and explicit saved modes.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function clientSettings(): array
    {
        return [
            'new client'       => [['id' => 0], 'include'],
            'legacy client'    => [['id' => 12, 'params' => []], 'exclude'],
            'include selected' => [['id' => 12, 'params' => ['tools_mode' => 'include', 'tools' => []]], 'include'],
            'exclude selected' => [['id' => 12, 'params' => ['tools_mode' => 'exclude', 'tools' => ['purgeCache']]], 'exclude'],
        ];
    }
}
