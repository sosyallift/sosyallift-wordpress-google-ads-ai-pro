<?php
namespace SosyalliftAIPro\Core\Rest;

use SosyalliftAIPro\Core\Integrations\Google\AdsService;

defined('ABSPATH') || exit;

class GoogleStatsEndpoint {

    public static function register(): void {

        register_rest_route('sl-ai/v1', '/google/ads', [
            'methods'  => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true'
        ]);
    }

    public static function handle() {
        return AdsService::get_stats();
    }
}
