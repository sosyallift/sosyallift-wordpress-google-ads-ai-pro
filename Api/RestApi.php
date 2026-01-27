<?php
namespace SosyalliftAIPro\Api;

class RestApi {
    public static function register_routes(): void {
        // Basit REST API endpoint'leri
        register_rest_route('sl-ai-pro/v1', '/test', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'test_endpoint'],
            'permission_callback' => '__return_true',
        ]);
        
        register_rest_route('sl-ai-pro/v1', '/status', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'status_endpoint'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);
    }
    
    public static function test_endpoint(): \WP_REST_Response {
        return new \WP_REST_Response([
            'status' => 'success',
            'message' => 'API çalışıyor',
            'timestamp' => time(),
        ], 200);
    }
    
    public static function status_endpoint(): \WP_REST_Response {
        return new \WP_REST_Response([
            'plugin' => 'Sosyallift AI Pro',
            'version' => SL_AI_PRO_VERSION,
            'active' => true,
            'modules' => [
                'google_ads' => class_exists('\SosyalliftAIPro\Modules\GoogleAds\Manager'),
                'seo' => class_exists('\SosyalliftAIPro\Modules\SEO\Manager'),
                'intent' => class_exists('\SosyalliftAIPro\Modules\Intent\Manager'),
                'intelligence' => class_exists('\SosyalliftAIPro\Modules\Intelligence\Manager'),
            ],
        ], 200);
    }
}