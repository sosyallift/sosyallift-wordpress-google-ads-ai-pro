<?php
namespace SosyalliftAIPro\Core\Integrations\Google;

defined('ABSPATH') || exit;

class TokenRepository {

    private static function key(): string {
        return hash('sha256', AUTH_KEY);
    }

    public static function store(array $token): void {

        $encrypted = openssl_encrypt(
            json_encode($token),
            'AES-256-CBC',
            self::key(),
            0,
            substr(self::key(), 0, 16)
        );

        is_multisite()
            ? update_site_option('sl_ai_google_token', $encrypted)
            : update_option('sl_ai_google_token', $encrypted);
    }

    public static function get(): ?array {

        $encrypted = is_multisite()
            ? get_site_option('sl_ai_google_token')
            : get_option('sl_ai_google_token');

        if (!$encrypted) {
            return null;
        }

        $json = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            self::key(),
            0,
            substr(self::key(), 0, 16)
        );

        return json_decode($json, true);
    }
}
