<?php
namespace SosyalliftAIPro\Core\Rest;

defined('ABSPATH') || exit;

class Permissions {

    public static function admin_only() {
        return current_user_can('manage_options');
    }

    public static function internal_agent() {

        // Cron veya internal token
        if (defined('DOING_CRON') && DOING_CRON) {
            return true;
        }

        // Admin UI
        if (current_user_can('manage_options')) {
            return true;
        }

        // Header token (future runner / AI agent)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $token   = $headers['X-Sosyallift-Agent'] ?? null;

        $stored = get_option('sosyallift_agent_token');

        return $token && hash_equals($stored, $token);
    }
}
