<?php
namespace SosyalliftAIPro\Core\Rest;

use WP_REST_Response;

defined('ABSPATH') || exit;

class StatsController {

    public static function register_routes() {

        register_rest_route('sosyallift/v1', '/stats', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stats'],
            'permission_callback' => [Permissions::class, 'admin_only'],
        ]);
    }

    public static function get_stats() {

        global $wpdb;

        $table = $wpdb->prefix . 'sosyallift_stats';

        $data = $wpdb->get_results(
            "SELECT metric, value FROM {$table}",
            OBJECT_K
        );

        return new WP_REST_Response([
            'status' => 'ok',
            'data'   => $data
        ]);
    }
}
