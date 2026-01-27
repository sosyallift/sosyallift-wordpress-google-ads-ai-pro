<?php
namespace SosyalliftAIPro\Core\Integrations\Google;

use SosyalliftAIPro\Core\Cache\HybridCache;
use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

Logger::get_instance()->log(
    LogTypes::ADS,
    'AdsService',
    'Ads data fetched',
    ['account' => $accountId]
);

defined('ABSPATH') || exit;

class AdsService {

    public static function get_stats(): array {

        if (!RateLimiter::allow('sl_ai_ads', 20, 60)) {
            return ['error' => 'rate_limited'];
        }

        $cache = HybridCache::get('sl_ai_ads_stats');
        if ($cache) {
            return $cache;
        }

        // 🔜 gerçek Ads API burada çağrılır
        $data = [
            'clicks' => 0,
            'cost'   => 0,
            'roas'   => 0,
        ];

        HybridCache::set('sl_ai_ads_stats', $data, 300);
        return $data;
    }
}
