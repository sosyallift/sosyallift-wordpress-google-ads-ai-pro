<?php
namespace SosyalliftAIPro\Core\Admin;

use SosyalliftAIPro\Core\Rest\StatsEndpoint;

defined('ABSPATH') || exit;

class AjaxController {

    public static function init(): void {
        add_action('wp_ajax_sl_ai_pro_dashboard_stats', [self::class, 'dashboard_stats']);
    }

    public static function dashboard_stats(): void {

        check_ajax_referer('sl_ai_pro_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $request  = new \WP_REST_Request('GET', '/' . StatsEndpoint::NAMESPACE . StatsEndpoint::ROUTE);
        $response = rest_do_request($request);

        if ($response->is_error()) {
            wp_send_json_error('REST Error', 500);
        }

        wp_send_json_success($response->get_data());
    }
}
