<?php
namespace SosyalliftAIPro\Core\Integrations\Google;
use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

Logger::get_instance()->log(
    LogTypes::OAUTH,
    'OAuthManager',
    'Access token refreshed'
);

defined('ABSPATH') || exit;

class OAuthManager {

    public static function get_auth_url(): string {

        $params = [
            'client_id'     => get_option('sl_ai_google_client_id'),
            'redirect_uri'  => admin_url('admin.php?page=sl-ai-pro-api'),
            'response_type' => 'code',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'scope'         => implode(' ', [
                'https://www.googleapis.com/auth/adwords',
                'https://www.googleapis.com/auth/webmasters.readonly'
            ])
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public static function exchange_code(string $code): array {

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => get_option('sl_ai_google_client_id'),
                'client_secret' => get_option('sl_ai_google_client_secret'),
                'redirect_uri'  => admin_url('admin.php?page=sl-ai-pro-api'),
                'grant_type'    => 'authorization_code',
            ]
        ]);

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
