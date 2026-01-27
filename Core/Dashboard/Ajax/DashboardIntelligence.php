<?php
namespace SosyalliftAIPro\Core\Dashboard\Ajax;

use SosyalliftAIPro\Core\Dashboard\DataProvider;
use SosyalliftAIPro\Core\Intelligence\IntelligenceKernel;

defined('ABSPATH') || exit;

class DashboardIntelligence {

    public static function register(): void {
        add_action('wp_ajax_sl_ai_pro_intelligence', [self::class, 'handle']);
    }

    public static function handle(): void {

        check_ajax_referer('sl_ai_pro_nonce', 'nonce');

        $payload = DataProvider::get_dashboard_payload();
        $result  = IntelligenceKernel::run($payload);

        wp_send_json_success([
            'intelligence' => $result->toArray(),
            'timestamp'    => current_time('mysql'),
        ]);
    }
}
