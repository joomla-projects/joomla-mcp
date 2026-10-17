<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MCP
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Auth;

use Joomla\Component\MCP\Api\Auth\TokenInfo;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests loading the authenticated client's Joomla parameters.
 *
 * @since  __DEPLOY_VERSION__
 */
class TokenInfoTest extends UnitTestCase
{
    /**
     * Client settings are carried from the database into the endpoint.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testClientParametersAreLoadedFromDatabaseJson(): void
    {
        $params = ['tools_mode' => 'include', 'tools' => ['thirdparty.read'], 'exclude_descriptions' => 1, 'custom' => 'retained'];
        $token  = TokenInfo::fromArray(['user_id' => 42, 'params' => json_encode($params)]);

        $this->assertSame(42, $token->userid);
        $this->assertInstanceOf(Registry::class, $token->params);
        $this->assertSame($params, $token->params->toArray());
        $this->assertSame('include', $token->params->get('tools_mode'));
    }

    /**
     * Existing clients without settings retain an empty parameter set.
     *
     * @param   array  $data  Legacy database row
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[DataProvider('legacyRows')]
    public function testLegacyParametersRemainEmpty(array $data): void
    {
        $this->assertSame([], TokenInfo::fromArray($data)->params->toArray());
    }

    /**
     * Each token owns its client settings, including before database loading.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDefaultParametersAreIndependentRegistries(): void
    {
        $first  = new TokenInfo();
        $second = new TokenInfo();
        $first->params->set('tools_mode', 'include');

        $this->assertNotSame($first->params, $second->params);
        $this->assertNull($second->params->get('tools_mode'));
    }

    /**
     * Historical parameter representations.
     *
     * @return  array
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function legacyRows(): array
    {
        return [
            'absent'       => [[]],
            'null'         => [['params' => null]],
            'empty string' => [['params' => '']],
            'empty object' => [['params' => '{}']],
        ];
    }
}
