<?php

/**
 * @package         Joomla.MCP
 * @subpackage      com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Core;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;

/**
 * Records MCP tool calls.
 *
 * - Write tool calls are added to the User Actions Log, so administrators can see which changes were made through
 *   which MCP client.
 * - Optionally, every tool call is written to a debug log file with its arguments redacted. Tool results are never
 *   logged, because they can contain personal data.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ToolCallLogger
{
    /**
     * The tool ran and returned a regular result
     *
     * @since  __DEPLOY_VERSION__
     */
    public const OUTCOME_SUCCESS = 'success';

    /**
     * The tool ran and returned an error result
     *
     * @since  __DEPLOY_VERSION__
     */
    public const OUTCOME_ERROR = 'error';

    /**
     * The tool threw an exception
     *
     * @since  __DEPLOY_VERSION__
     */
    public const OUTCOME_EXCEPTION = 'exception';

    /**
     * The log category and file name of the debug log
     *
     * @since  __DEPLOY_VERSION__
     */
    private const LOG_CATEGORY = 'mcp';

    /**
     * Whether the debug log file logger has been registered in this request
     *
     * @since  __DEPLOY_VERSION__
     */
    private static bool $loggerRegistered = false;

    /**
     * Writes an entry to the User Actions Log: function (array $message, string $languageKey, int $userId): void
     *
     * @since  __DEPLOY_VERSION__
     */
    private \Closure $actionLogWriter;

    /**
     * Writes a line to the debug log: function (string $line): void
     *
     * @since  __DEPLOY_VERSION__
     */
    private \Closure $debugLogWriter;

    /**
     * Constructor.
     *
     * @param  ArgumentRedactor  $redactor     Used to redact the arguments in the debug log
     * @param  string         $clientName       The name of the MCP client making the calls
     * @param  boolean        $actionLog        Whether write tool calls are added to the User Actions Log
     * @param  boolean        $debugLog         Whether all tool calls are written to the debug log file
     * @param  \Closure|null  $actionLogWriter  Replaces the User Actions Log writer, e.g. in tests
     * @param  \Closure|null  $debugLogWriter   Replaces the debug log writer, e.g. in tests
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(
        private readonly ArgumentRedactor $redactor,
        private readonly string $clientName,
        private readonly bool $actionLog = true,
        private readonly bool $debugLog = false,
        ?\Closure $actionLogWriter = null,
        ?\Closure $debugLogWriter = null,
    ) {
        $this->actionLogWriter = $actionLogWriter ?? self::writeActionLog(...);
        $this->debugLogWriter  = $debugLogWriter ?? self::writeDebugLog(...);
    }

    /**
     * Record a tool call
     *
     * Logging never interrupts the call: failures to write a log entry are ignored.
     *
     * @param  string   $toolName    The tool name
     * @param  boolean  $readOnly    Whether the tool is read-only
     * @param  array    $arguments   The call arguments
     * @param  string   $outcome     One of the OUTCOME_* constants
     * @param  float    $durationMs  The duration of the call in milliseconds
     * @param  User     $user        The user the call ran as
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function log(
        string $toolName,
        bool $readOnly,
        array $arguments,
        string $outcome,
        float $durationMs,
        User $user
    ): void {
        if ($this->actionLog && !$readOnly) {
            try {
                [$message, $languageKey] = $this->buildActionLogMessage($toolName, $outcome, $user);
                ($this->actionLogWriter)($message, $languageKey, (int) $user->id);
            } catch (\Throwable) {
                // The action log is informational; it must not break the tool call
            }
        }

        if ($this->debugLog) {
            try {
                ($this->debugLogWriter)($this->formatDebugLine($toolName, $readOnly, $arguments, $outcome, $durationMs, $user));
            } catch (\Throwable) {
                // Same as above
            }
        }
    }

    /**
     * Build the User Actions Log message for a write tool call
     *
     * @param  string  $toolName  The tool name
     * @param  string  $outcome   One of the OUTCOME_* constants
     * @param  User    $user      The user the call ran as
     *
     * @return  array  The message and the language key
     *
     * @since   __DEPLOY_VERSION__
     */
    public function buildActionLogMessage(string $toolName, string $outcome, User $user): array
    {
        $message = [
            'action'      => 'mcp',
            'tool'        => $toolName,
            'client'      => $this->clientName,
            'outcome'     => $outcome,
            'userid'      => (int) $user->id,
            'username'    => (string) $user->username,
            'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . (int) $user->id,
        ];

        $languageKey = match ($outcome) {
            self::OUTCOME_SUCCESS => 'COM_MCP_ACTIONLOG_TOOL_CALLED',
            default               => 'COM_MCP_ACTIONLOG_TOOL_FAILED',
        };

        return [$message, $languageKey];
    }

    /**
     * Format the debug log line for a tool call
     *
     * @param  string   $toolName    The tool name
     * @param  boolean  $readOnly    Whether the tool is read-only
     * @param  array    $arguments   The call arguments
     * @param  string   $outcome     One of the OUTCOME_* constants
     * @param  float    $durationMs  The duration of the call in milliseconds
     * @param  User     $user        The user the call ran as
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function formatDebugLine(
        string $toolName,
        bool $readOnly,
        array $arguments,
        string $outcome,
        float $durationMs,
        User $user
    ): string {
        $arguments = json_encode(
            $this->redactor->redact($arguments),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return \sprintf(
            'client="%s" user=%d tool="%s" type=%s outcome=%s duration=%dms arguments=%s',
            $this->clientName,
            (int) $user->id,
            $toolName,
            $readOnly ? 'read' : 'write',
            $outcome,
            (int) round($durationMs),
            $arguments === false ? '{}' : $arguments
        );
    }

    /**
     * Add an entry to the User Actions Log
     *
     * @param  array    $message      The message data
     * @param  string   $languageKey  The language key of the message
     * @param  integer  $userId       The user the call ran as
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function writeActionLog(array $message, string $languageKey, int $userId): void
    {
        /** @var \Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel $model */
        $model = Factory::getApplication()->bootComponent('com_actionlogs')
            ->getMVCFactory()->createModel('Actionlog', 'Administrator', ['ignore_request' => true]);

        $model->addLog([$message], $languageKey, 'com_mcp', $userId);
    }

    /**
     * Write a line to the debug log file (administrator/logs/mcp.php by default)
     *
     * @param  string  $line  The log line
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function writeDebugLog(string $line): void
    {
        if (!self::$loggerRegistered) {
            Log::addLogger(['text_file' => self::LOG_CATEGORY . '.php'], Log::ALL, [self::LOG_CATEGORY]);
            self::$loggerRegistered = true;
        }

        Log::add($line, Log::INFO, self::LOG_CATEGORY);
    }
}
