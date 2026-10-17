<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Administrator\Table;

use Joomla\Component\MCP\Administrator\Table\McpTable;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests standard parameter persistence during client edits and token regeneration.
 *
 * @since  __DEPLOY_VERSION__
 */
class McpTableTest extends UnitTestCase
{
    /**
     * Both supported selection modes survive normal table serialization.
     *
     * @param   string  $mode  The selection mode.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[DataProvider('selectionModes')]
    public function testToolSettingsAreSavedAndPreservedWhenTheTokenChanges(string $mode): void
    {
        $table  = $this->createTable();
        $params = ['tools_mode' => $mode, 'tools' => ['purgeCache', 'thirdParty.read'], 'exclude_descriptions' => 1];

        $this->assertTrue($table->bind(['id' => 12, 'params' => $params]));
        $this->assertSame($params, (new Registry($table->params))->toArray());

        // The table's load() method exposes persisted parameters as an array.
        $table->params = (new Registry($table->params))->toArray();
        $this->assertTrue($table->bind(['client_token' => 'replacement-token']));
        $this->assertSame('replacement-token', $table->client_token);
        $this->assertSame($params, (new Registry($table->params))->toArray());
    }

    /**
     * Include and exclude use the same standard storage path.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function selectionModes(): array
    {
        return [['include'], ['exclude']];
    }

    /**
     * A cleared selection replaces the previously stored names.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClearingTheSelectionReplacesSavedTools(): void
    {
        $table = $this->createTable();
        $table->bind(['params' => ['tools_mode' => 'include', 'tools' => ['purgeCache']]]);
        $table->bind(['params' => ['tools_mode' => 'include', 'exclude_descriptions' => 0]]);

        $this->assertSame(
            ['tools_mode' => 'include', 'exclude_descriptions' => 0],
            (new Registry($table->params))->toArray()
        );
    }

    /**
     * Token regeneration does not turn an unrestricted legacy client into an explicit selection.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testLegacyParametersRemainUnconfiguredWhenTheTokenChanges(): void
    {
        $table = $this->createTable();
        $table->bind(['id' => 12, 'params' => '{}']);
        $table->bind(['client_token' => 'replacement-token']);

        $this->assertSame('{}', $table->params);
    }

    /**
     * Exercise the real bind method without querying the table schema.
     *
     * @return  McpTable
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createTable(): McpTable
    {
        $table = (new \ReflectionClass(McpTable::class))->newInstanceWithoutConstructor();
        $table->setDispatcher(new Dispatcher());

        return $table;
    }
}
