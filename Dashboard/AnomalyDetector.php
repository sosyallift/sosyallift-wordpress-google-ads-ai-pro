<?php
namespace SosyalliftAIPro\Core\Dashboard;

defined('ABSPATH') || exit;

class AnomalyDetector {

    public static function report(): array {
        global $wpdb;

        $suspicious = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_logs
            WHERE duration < 3 AND pages_viewed = 1
        ");

        return [
            'suspicious_sessions' => $suspicious,
            'risk_level' => $suspicious > 50 ? 'high' : 'normal',
        ];
    }
}
