<?php
namespace SosyalliftAIPro\Core\Integrations\Google;
use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

Logger::get_instance()->log(
    LogTypes::RATE_LIMIT,
    'RateLimiter',
    'Rate limit hit',
    ['service' => 'google']
);

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
