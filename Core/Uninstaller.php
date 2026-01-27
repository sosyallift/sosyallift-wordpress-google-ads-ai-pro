<?php
namespace SosyalliftAIPro\Core;

use SosyalliftAIPro\Core\Helpers\UninstallHelper;
use SosyalliftAIPro\Core\Database\Migrations;
use SosyalliftAIPro\Core\Helpers\UninstallLogger;

defined('ABSPATH') || exit;

class Uninstaller {

    public static function uninstall(): void {

        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        if (is_admin() && !current_user_can('delete_plugins')) {
            return;
        }

        if (is_multisite()) {
            self::uninstall_multisite();
        } else {
            self::uninstall_single();
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sosyallift AI Pro] Uninstall completed successfully.');
        }
    }

    private static function uninstall_single(): void {
        global $wpdb;

        if (class_exists(UninstallHelper::class) && !UninstallHelper::should_remove_data()) {
            self::delete_cron_jobs();
            self::flush_cache();
            return;
        }

        self::delete_tables($wpdb);
        self::delete_options($wpdb);
        self::delete_transients($wpdb);
        self::delete_cron_jobs();
        self::delete_user_meta($wpdb);
        self::delete_post_meta($wpdb);
        self::delete_term_meta($wpdb);
        self::delete_uploads();
        self::delete_logs();
        self::flush_cache();

        // Migration rollback
        Migrations::rollback_all();

        flush_rewrite_rules(false);
    }

    private static function uninstall_multisite(): void {
        $sites = get_sites(['number' => 0, 'deleted' => 0, 'archived' => 0]);

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            self::uninstall_single();
            restore_current_blog();
        }
    }

    private static function delete_tables($wpdb): void {
        if (!UninstallHelper::can_delete('delete_tables')) {
            return;
        }

        $tables = [
            'sl_ai_logs',
            'sl_ai_keywords',
            'sl_ai_scores',
            'sl_ai_alerts',
            'sl_ai_campaigns',
            'sl_ai_conversions',
            'sl_ai_intent',
            'sl_ai_migrations',
            'sl_ai_pages',
            'sl_ai_seo_data',
            'sl_ai_analytics',
            'sl_ai_cron_jobs',
            'sl_ai_settings',
            'sl_ai_templates',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`");
        }
    }

    private static function delete_options($wpdb): void {
        if (!UninstallHelper::can_delete('delete_options')) {
            return;
        }

        $patterns = [
            'sl_ai_%',
            'sl_ai_pro_%',
            'sosyallift_%',
            'ai_intelligence_%',
        ];

        foreach ($patterns as $pattern) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                )
            );
        }
    }

    private static function delete_transients($wpdb): void {
        if (!UninstallHelper::can_delete('delete_transients')) {
            return;
        }

        $patterns = [
            '_transient_%sl_ai_%',
            '_transient_timeout_%sl_ai_%',
            '_site_transient_%sl_ai_%',
            '_site_transient_timeout_%sl_ai_%',
        ];

        foreach ($patterns as $pattern) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                )
            );
        }
    }

    private static function delete_cron_jobs(): void {
        if (!UninstallHelper::can_delete('delete_cron_jobs')) {
            return;
        }

        $hooks = [
            'sl_ai_daily_sync',
            'sl_ai_hourly_check',
            'sl_ai_weekly_report',
            'sosyallift_ai_sync',
            'ai_intelligence_cron',
            'google_ads_ai_update',
            'sl_ai_pro_cron_sync',
            'sl_ai_pro_cron_cleanup',
            'sl_ai_pro_cron_reporting',
            'sl_ai_pro_cron_license_check',
        ];

        foreach ($hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    private static function delete_user_meta($wpdb): void {
        if (!UninstallHelper::can_delete('delete_user_meta')) {
            return;
        }

        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta}
             WHERE meta_key LIKE 'sl_ai_%'
             OR meta_key LIKE 'sosyallift_%'"
        );
    }

    private static function delete_post_meta($wpdb): void {
        if (!UninstallHelper::can_delete('delete_post_meta')) {
            return;
        }

        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta}
             WHERE meta_key LIKE '_sl_ai_%'
             OR meta_key LIKE '_ai_%'"
        );
    }

    private static function delete_term_meta($wpdb): void {
        if (!UninstallHelper::can_delete('delete_term_meta')) {
            return;
        }

        $wpdb->query(
            "DELETE FROM {$wpdb->termmeta}
             WHERE meta_key LIKE 'sl_ai_%'"
        );
    }

    private static function delete_uploads(): void {
        if (!UninstallHelper::can_delete('delete_uploads')) {
            return;
        }

        $upload = wp_upload_dir();

        $dirs = [
            $upload['basedir'] . '/sosyallift-ai/',
            $upload['basedir'] . '/sl-ai-exports/',
            $upload['basedir'] . '/ai-intelligence/',
            WP_CONTENT_DIR . '/cache/sosyallift-ai-pro/',
        ];

        foreach ($dirs as $dir) {
            self::delete_dir($dir);
        }
    }

    private static function delete_logs(): void {
        if (!UninstallHelper::can_delete('delete_logs')) {
            return;
        }

        $logs = [
            WP_CONTENT_DIR . '/sl-ai-debug.log',
            WP_CONTENT_DIR . '/sosyallift-errors.log',
            WP_CONTENT_DIR . '/sl-ai-uninstall.log',
        ];

        foreach ($logs as $log) {
            if (file_exists($log)) {
                @unlink($log);
            }
        }
    }

    private static function flush_cache(): void {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache();
        }
    }

    private static function delete_dir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::delete_dir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
