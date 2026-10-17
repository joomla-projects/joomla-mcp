<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Mcp.Joomla
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\MCP\Joomla\Prompt;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MCP\Prompt\PromptInterface;
use Joomla\CMS\MCP\Prompt\PromptResult;

/**
 * Prompt for a read-only health check of the site configuration.
 *
 * Relies on the getSysInfo and getApplicationConfig tools registered by this plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
final class SiteHealthCheck implements PromptInterface
{
    /**
     * Supported environments, the first one is the default
     *
     * @var    string[]
     * @since  __DEPLOY_VERSION__
     */
    private const ENVIRONMENTS = ['production', 'staging', 'development'];

    /**
     * Instructions for the model. Kept in English on purpose, the model answers in the user's language.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    private const INSTRUCTIONS = <<<'TEXT'
        Run a health check of this Joomla site. The site is a %s environment.

        This is a read-only check. Do not change anything: do not call any tool that creates, updates or deletes data, and do not purge the cache.

        1. Call the tool getSysInfo to get the Joomla version, the PHP version and the server time.
        2. Call the tool getApplicationConfig to get the global configuration as JSON.
        3. Evaluate the following areas, using the configuration keys in brackets:
           - Versions: Joomla version (flag development, alpha, beta or release candidate builds on production) and PHP version. Only call a PHP version end-of-life if you are certain; otherwise recommend checking https://www.php.net/supported-versions.php.
           - Debugging and errors [debug, debug_lang, error_reporting, log_everything, log_deprecated]: on production, debug should be off and error_reporting should be "none" or "default".
           - Availability and indexing [offline, robots]: an offline site or "noindex" on production is critical.
           - HTTPS and security [force_ssl, cors, cors_allow_origin, frontediting, captcha]: force_ssl 2 (entire site) is recommended; CORS with origin "*" is a risk.
           - SEO [sef, sef_rewrite, sitename_pagetitles, MetaDesc]
           - Performance [caching, cachetime, gzip, cache_handler]
           - Sessions [session_handler, lifetime, shared_session]
           - Mail [mailonline, mailer, smtpsecure, smtpauth]: SMTP without encryption is a risk; mailonline off means the site sends no mail.
        4. Rate each finding as OK, Warning or Critical, taking the environment into account: settings that are fine for development can be a warning or critical on production.

        Answer with:
        - A short summary with the number of Critical, Warning and OK findings.
        - A table with the columns Area, Setting, Current value, Rating and Recommendation, sorted by rating (Critical first).
        - For Critical and Warning findings, where to change the setting in the Joomla administrator (for example System > Global Configuration > Server).
        TEXT;

    public function getName(): string
    {
        return 'siteHealthCheck';
    }

    public function getTitle(): string
    {
        return Text::_('PLG_MCP_JOOMLA_SITE_HEALTH_CHECK_TITLE');
    }

    public function getDescription(): string
    {
        return Text::_('PLG_MCP_JOOMLA_SITE_HEALTH_CHECK_DESC');
    }

    public function getArguments(): array
    {
        return [
            [
                'name'        => 'environment',
                'description' => Text::_('PLG_MCP_JOOMLA_SITE_HEALTH_CHECK_ENVIRONMENT_DESC'),
                'required'    => false,
            ],
        ];
    }

    public function getMessages(array $arguments): PromptResult
    {
        $environment = strtolower(trim($arguments['environment'] ?? ''));

        // Unknown values fall back to production, the strictest check
        if (!\in_array($environment, self::ENVIRONMENTS, true)) {
            $environment = self::ENVIRONMENTS[0];
        }

        return PromptResult::text(\sprintf(self::INSTRUCTIONS, $environment))
            ->withDescription(Text::sprintf('PLG_MCP_JOOMLA_SITE_HEALTH_CHECK_RESULT_DESC', $environment));
    }
}
