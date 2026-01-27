<?php
namespace SosyalliftAIPro\Core\Rest;

use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

class StatsEndpoint {

    const NAMESPACE = 'sosyallift-ai/v1';
    const ROUTE     = '/stats';

    public static function register(): void {

        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => 'GET',
            'callback'            => [self::class, 'handle'],
            'permission_callback' => [self::class, 'permission'],
        ]);
    }

    public static function permission(): bool {
        return current_user_can('manage_options');
    }

    public static function handle(WP_REST_Request $request): WP_REST_Response {

        /**
         * Local-first placeholder
         * (ileride Ads / GA / SC / Logs buraya bağlanacak)
         */

        $data = [
            'visitors'     => rand(20, 120),
            'ads_clicks'   => rand(5, 40),
            'conversions'  => rand(1, 10),
            'anomaly'      => false,
            'timestamp'    => current_time('mysql'),
        ];

        return new WP_REST_Response($data, 200);
    }
}
