<?php
namespace SosyalliftAIPro\Core\Dashboard;

defined('ABSPATH') || exit;

class MetricsAggregator {

    public static function traffic(): array {
        global $wpdb;

        return [
            'sessions'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_logs"),
            'unique'     => (int) $wpdb->get_var("SELECT COUNT(DISTINCT ip) FROM {$wpdb->prefix}sl_ai_logs"),
            'devices'    => self::device_split(),
            'hourly'     => self::hourly_heatmap(),
        ];
    }

    public static function seo(): array {
        global $wpdb;

        return [
            'impressions' => (int) $wpdb->get_var("SELECT SUM(impressions) FROM {$wpdb->prefix}sl_ai_seo"),
            'clicks'      => (int) $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}sl_ai_seo"),
            'avg_pos'     => (float) $wpdb->get_var("SELECT AVG(position) FROM {$wpdb->prefix}sl_ai_seo"),
        ];
    }

    public static function ads(): array {
        global $wpdb;

        return [
            'clicks'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_ads"),
            'keywords'    => (int) $wpdb->get_var("SELECT COUNT(DISTINCT keyword) FROM {$wpdb->prefix}sl_ai_ads"),
            'sources'     => self::ads_sources(),
        ];
    }

    private static function device_split(): array {
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT device, COUNT(*) as total
            FROM {$wpdb->prefix}sl_ai_logs
            GROUP BY device
        ", ARRAY_A);

        return $rows ?: [];
    }

    private static function hourly_heatmap(): array {
        global $wpdb;

        return $wpdb->get_results("
            SELECT HOUR(created_at) as hour, COUNT(*) as total
            FROM {$wpdb->prefix}sl_ai_logs
            GROUP BY hour
        ", ARRAY_A) ?: [];
    }

    private static function ads_sources(): array {
        global $wpdb;

        return $wpdb->get_results("
            SELECT source, COUNT(*) as total
            FROM {$wpdb->prefix}sl_ai_ads
            GROUP BY source
        ", ARRAY_A) ?: [];
    }
}
