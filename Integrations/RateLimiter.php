<?php
namespace SosyalliftAIPro\Core\Integrations\Google;

defined('ABSPATH') || exit;

class RateLimiter {

    public static function allow(string $key, int $limit, int $window): bool {

        $count = (int) get_transient($key);

        if ($count >= $limit) {
            return false;
        }

        set_transient($key, $count + 1, $window);
        return true;
    }
}
