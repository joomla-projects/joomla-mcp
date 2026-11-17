<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\MCP\Api\Core;

use Joomla\CMS\User\User;
use Joomla\Component\MCP\Api\Core\ArgumentRedactor;
use Joomla\Component\MCP\Api\Core\ToolCallLogger;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for the ToolCallLogger.
 *
 * @package     Joomla.UnitTest
 * @subpackage  Mcp
 * @since       __DEPLOY_VERSION__
 */
class ToolCallLoggerTest extends UnitTestCase
{
    /**
     * @var array  Entries written to the action log writer
     *
     * @since  __DEPLOY_VERSION__
     */
    private array $actionLog = [];

    /**
     * @var string[]  Lines written to the debug log writer
     *
     * @since  __DEPLOY_VERSION__
     */
    private array $debugLog = [];

    /**
     * Create a logger with recording writers
     *
     * @param  boolean  $actionLog  Whether the action log is enabled
     * @param  boolean  $debugLog   Whether the debug log is enabled
     *
     * @return  ToolCallLogger
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createLogger(bool $actionLog, bool $debugLog): ToolCallLogger
    {
        $this->actionLog = [];
        $this->debugLog  = [];

        return new ToolCallLogger(
            new ArgumentRedactor(['the-client-token']),
            'Test Client',
            $actionLog,
            $debugLog,
            function (array $message, string $languageKey, int $userId): void {
                $this->actionLog[] = [$message, $languageKey, $userId];
            },
            function (string $line): void {
                $this->debugLog[] = $line;
            }
        );
    }

    /**
     * Create a user
     *
     * @return  User
     *
     * @since   __DEPLOY_VERSION__
     */
    private function createUser(): User
    {
        $user           = new User();
        $user->id       = 42;
        $user->username = 'editor';

        return $user;
    }

    /**
     * A successful write call is added to the action log with tool, client and user.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testWriteCallIsAddedToActionLog(): void
    {
        $logger = $this->createLogger(true, false);
        $logger->log('content_articles_create', false, ['title' => 'Hi'], ToolCallLogger::OUTCOME_SUCCESS, 12.3, $this->createUser());

        $this->assertCount(1, $this->actionLog);

        [$message, $languageKey, $userId] = $this->actionLog[0];

        $this->assertSame('COM_MCP_ACTIONLOG_TOOL_CALLED', $languageKey);
        $this->assertSame(42, $userId);
        $this->assertSame('content_articles_create', $message['tool']);
        $this->assertSame('Test Client', $message['client']);
        $this->assertSame('editor', $message['username']);
        $this->assertSame('index.php?option=com_users&task=user.edit&id=42', $message['accountlink']);
        $this->assertArrayNotHasKey('arguments', $message);
        $this->assertSame([], $this->debugLog);
    }

    /**
     * Read-only calls are not added to the action log.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testReadOnlyCallIsNotAddedToActionLog(): void
    {
        $logger = $this->createLogger(true, false);
        $logger->log('content_articles_list', true, [], ToolCallLogger::OUTCOME_SUCCESS, 1.0, $this->createUser());

        $this->assertSame([], $this->actionLog);
    }

    /**
     * Failed calls use their own language key.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testOutcomeSelectsLanguageKey(): void
    {
        $logger = $this->createLogger(true, false);
        $user   = $this->createUser();

        $this->assertSame('COM_MCP_ACTIONLOG_TOOL_CALLED', $logger->buildActionLogMessage('t', ToolCallLogger::OUTCOME_SUCCESS, $user)[1]);
        $this->assertSame('COM_MCP_ACTIONLOG_TOOL_FAILED', $logger->buildActionLogMessage('t', ToolCallLogger::OUTCOME_ERROR, $user)[1]);
        $this->assertSame('COM_MCP_ACTIONLOG_TOOL_FAILED', $logger->buildActionLogMessage('t', ToolCallLogger::OUTCOME_EXCEPTION, $user)[1]);
    }

    /**
     * With both logs disabled nothing is written.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDisabledLogsWriteNothing(): void
    {
        $logger = $this->createLogger(false, false);
        $logger->log('purgeCache', false, [], ToolCallLogger::OUTCOME_SUCCESS, 1.0, $this->createUser());

        $this->assertSame([], $this->actionLog);
        $this->assertSame([], $this->debugLog);
    }

    /**
     * The debug log contains the call details, with secrets and credentials redacted.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testDebugLogRedactsArguments(): void
    {
        $logger = $this->createLogger(false, true);
        $logger->log(
            'users_create',
            false,
            ['username' => 'new', 'password' => 'Pa55word!', 'note' => 'token is the-client-token'],
            ToolCallLogger::OUTCOME_SUCCESS,
            7.6,
            $this->createUser()
        );

        $this->assertCount(1, $this->debugLog);

        $line = $this->debugLog[0];

        $this->assertStringContainsString('client="Test Client"', $line);
        $this->assertStringContainsString('user=42', $line);
        $this->assertStringContainsString('tool="users_create"', $line);
        $this->assertStringContainsString('type=write', $line);
        $this->assertStringContainsString('outcome=success', $line);
        $this->assertStringContainsString('duration=8ms', $line);
        $this->assertStringContainsString('"username":"new"', $line);
        $this->assertStringNotContainsString('Pa55word!', $line);
        $this->assertStringNotContainsString('the-client-token', $line);
    }

    /**
     * A failing writer does not break the tool call.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testWriterFailuresAreIgnored(): void
    {
        $logger = new ToolCallLogger(
            new ArgumentRedactor([]),
            'Test Client',
            true,
            true,
            static function (): void {
                throw new \RuntimeException('Database down');
            },
            static function (): void {
                throw new \RuntimeException('Disk full');
            }
        );

        $logger->log('purgeCache', false, [], ToolCallLogger::OUTCOME_SUCCESS, 1.0, $this->createUser());

        // Reaching this point without an exception is the assertion
        $this->addToAssertionCount(1);
    }
}
