<?php
namespace SosyalliftAIPro\Core\Cache;

defined('ABSPATH') || exit;

class HybridCache {

    public static function get(string $key) {
        return get_transient($key);
    }

    public static function set(string $key, $data, int $ttl = 300): void {
        set_transient($key, $data, $ttl);
    }
}
