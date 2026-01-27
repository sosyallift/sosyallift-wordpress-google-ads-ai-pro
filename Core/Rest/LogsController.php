<?php
namespace SosyalliftAIPro\Core\Rest;

use WP_REST_Response;

defined('ABSPATH') || exit;

class LogsController {

    public static function register_routes() {

        register_rest_route('sosyallift/v1', '/logs', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_logs'],
            'permission_callback' => [Permissions::class, 'admin_only'],
        ]);
    }

    public static function get_logs() {

        global $wpdb;

        $table = $wpdb->prefix . 'sosyallift_logs';

        $logs = $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200"
        );

        return new WP_REST_Response([
            'status' => 'ok',
            'logs'   => $logs
        ]);
    }
}
