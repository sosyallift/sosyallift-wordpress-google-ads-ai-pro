<?php
namespace SosyalliftAIPro\Core\Dashboard;

use SosyalliftAIPro\Core\Anomaly\AnomalyEngine;

defined('ABSPATH') || exit;

class DataProvider {

    public static function get_dashboard_payload(): array {

        $anomalyResult = AnomalyEngine::run();

        return [
            'system' => self::system_status(),

            'traffic' => class_exists(MetricsAggregator::class)
                ? MetricsAggregator::traffic()
                : ['visitors' => 0],

            'seo' => class_exists(MetricsAggregator::class)
                ? MetricsAggregator::seo()
                : ['health' => 'unknown'],

            'ads' => class_exists(MetricsAggregator::class)
                ? MetricsAggregator::ads()
                : ['clicks' => 0],

            'intent' => class_exists(IntentResolver::class)
                ? IntentResolver::summary()
                : ['top' => 'unknown'],

            'anomaly' => $anomalyResult->toArray(),

            'timestamp' => current_time('mysql'),
        ];
    }

    private static function system_status(): array {
        return [
            'php'      => PHP_VERSION,
            'wp'       => get_bloginfo('version'),
            'plugin'   => SL_AI_PRO_VERSION,
            'memory'   => ini_get('memory_limit'),
            'timezone' => wp_timezone_string(),
            'mode'     => defined('WP_DEBUG') && WP_DEBUG ? 'debug' : 'production',
        ];
    }
}
