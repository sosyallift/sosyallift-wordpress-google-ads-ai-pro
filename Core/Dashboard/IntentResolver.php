<?php
namespace SosyalliftAIPro\Core\Dashboard;

defined('ABSPATH') || exit;

class IntentResolver {

    public static function summary(): array {
        global $wpdb;

        return [
            'commercial'     => self::count('commercial'),
            'informational'  => self::count('informational'),
            'navigational'   => self::count('navigational'),
            'unknown'        => self::count('unknown'),
        ];
    }

    private static function count(string $type): int {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_intent WHERE intent_type = %s",
                $type
            )
        );
    }
}
