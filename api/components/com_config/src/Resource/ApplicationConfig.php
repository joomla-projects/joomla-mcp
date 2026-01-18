<?php

/**
 * @package     Joomla.API
 * @subpackage  com_config
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Config\Api\Resource;

use Joomla\CMS\WebService\Resource\Attribute\Property\Description;
use Joomla\CMS\WebService\Resource\Attribute\Property\Guarded;
use Joomla\CMS\WebService\Resource\Resource;

class ApplicationConfig extends Resource
{
    public function __construct(
        #[Guarded]
        #[Description("Extension ID (Joomla file extension)")]
        public int $id,

        // Site Settings
        public string $sitename,
        #[Description("use 0 for site offline, 1 for online")]
        public int $offline,
        public string $offline_message,
        public string $offline_image,
        #[Description("use 1 to display offline message, 0 to hide")]
        public int $display_offline_message,
        public string $editor,
        public int $captcha,
        public int $list_limit,
        public int $access,

        // Metadata Settings
        #[Description("Meta description for the site")]
        public string $MetaDesc,
        public string $MetaAuthor,
        public string $MetaVersion,
        public string $MetaRights,
        public string $robots,

        // SEO Settings
        #[Description("use 1 for SEF URLs, 0 for standard")]
        public int $sef,
        #[Description("use 1 for URL rewriting, 0 for disabled")]
        public int $sef_rewrite,
        #[Description("use 1 to add .html suffix, 0 for no suffix")]
        public int $sef_suffix,
        #[Description("use 1 for Unicode aliases, 0 for standard")]
        public int $unicodeslugs,
        #[Description("use 0 for sitename after, 1 for sitename before, 2 for no sitename")]
        public int $sitename_pagetitles,

        // Cookie Settings
        public string $cookie_domain,
        public string $cookie_path,

        // Database Settings
        #[Guarded]
        public string $dbtype,
        #[Guarded]
        public string $host,
        #[Guarded]
        public string $db,
        #[Guarded]
        public string $dbprefix,
        #[Guarded]
        public string $dbencryption,
        #[Guarded]
        public string $dbsslverifyservercert,
        #[Guarded]
        public string $dbsslca,
        #[Guarded]
        public string $dbsslkey,
        #[Guarded]
        public string $dbsslcert,
        #[Guarded]
        public string $dbsslcipher,
        #[Guarded]
        public string $user,

        // Server Settings
        public string $tmp_path,
        public string $log_path,
        #[Description("use 1 to enable gzip compression, 0 for disabled")]
        public int $gzip,
        #[Description("use 0 for none, 1 for simple, 2 for maximum")]
        public int $error_reporting,
        #[Description("use 0 for disabled, 1 for enabled")]
        public int $force_ssl,
        public string $session_handler,
        #[Description("Session lifetime in minutes")]
        public int $lifetime,
        public string $session_filesystem_path,
        public string $session_memcached_server_host,
        public int $session_memcached_server_port,
        public string $session_redis_server_host,
        public int $session_redis_server_port,
        public int $session_redis_persist,
        #[Guarded]
        public string $session_redis_server_auth,
        public int $session_redis_server_db,
        #[Description("use 1 to enable session metadata, 0 for disabled")]
        public int $session_metadata,
        public int $session_metadata_for_guest,
        #[Description("use 1 for shared sessions, 0 for separate")]
        public int $shared_session,

        // Locale Settings
        public string $offset,
        public string $locale,

        // Cache Settings
        #[Description("use 0 for off, 1 for conservative, 2 for progressive")]
        public int $caching,
        public string $cache_handler,
        #[Description("Cache time in minutes")]
        public int $cachetime,
        public string $cache_path,
        #[Description("use 1 for platform prefix, 0 for disabled")]
        public int $cache_platformprefix,
        public int $memcached_persist,
        public int $memcached_compress,
        public string $memcached_server_host,
        public int $memcached_server_port,
        public int $redis_persist,
        public string $redis_server_host,
        public int $redis_server_port,
        #[Guarded]
        public string $redis_server_auth,
        public int $redis_server_db,

        // Debug Settings
        #[Description("use 1 for debug mode, 0 for disabled")]
        public int $debug,
        #[Description("use 1 for language debug, 0 for disabled")]
        public int $debug_lang,
        #[Description("use 1 to show language constants, 0 for disabled")]
        public int $debug_lang_const,

        // Logging Settings
        #[Description("use 0 for disabled, 1 for enabled")]
        public int $logging,
        public string $log_categories,
        #[Description("use 0 for include mode, 1 for exclude mode")]
        public int $log_category_mode,
        public string $log_priorities,
        #[Description("use 1 to log everything, 0 for priorities only")]
        public int $log_everything,
        #[Description("use 1 to log deprecated API, 0 for disabled")]
        public int $log_deprecated,
        public string $logging_custom,

        // Mail Settings
        public string $mailer,
        public string $mailfrom,
        public string $fromname,
        public string $replyto,
        public string $replytoname,
        public string $sendmail,
        public string $smtpauth,
        #[Guarded]
        public string $smtpuser,
        #[Guarded]
        public string $smtppass,
        public string $smtphost,
        public int $smtpport,
        public string $smtpsecure,
        #[Description("use 1 to disable mass mail, 0 for enabled")]
        public int $massmailoff,
        #[Description("use 1 to send mail when offline, 0 for disabled")]
        public int $mailonline,

        // Feed Settings
        public int $feed_limit,
        public string $feed_email,

        // Proxy Settings
        #[Description("use 1 to enable proxy, 0 for disabled")]
        public int $proxy_enable,
        public string $proxy_host,
        public int $proxy_port,
        #[Guarded]
        public string $proxy_user,
        #[Guarded]
        public string $proxy_pass,

        // CORS Settings
        #[Description("use 1 to enable CORS, 0 for disabled")]
        public int $cors,
        public string $cors_allow_origin,
        public string $cors_allow_headers,
        public string $cors_allow_methods,

        // Permissions
        public string $asset_id,
        public string $rules,

        // Frontend Editing
        #[Description("use 1 to enable frontend editing, 0 for disabled")]
        public int $frontediting,

        // Behind Load Balancer
        #[Description("use 1 if behind load balancer, 0 for direct")]
        public int $behind_loadbalancer,
    )
    {
    }
}
