<?php
namespace SosyalliftAIPro\Core\Integrations\Google;

use SosyalliftAIPro\Core\Cache\HybridCache;

defined('ABSPATH') || exit;

class SearchConsoleService {

    public static function get_stats(): array {

        if (!RateLimiter::allow('sl_ai_gsc', 20, 60)) {
            return ['error' => 'rate_limited'];
        }

        $cache = HybridCache::get('sl_ai_gsc_stats');
        if ($cache) {
            return $cache;
        }

        // 🔜 gerçek Search Console API çağrısı burada
        $data = [
            'clicks'      => 0,
            'impressions' => 0,
            'ctr'         => 0,
            'position'    => 0,
        ];

        HybridCache::set('sl_ai_gsc_stats', $data, 300);
        return $data;
    }
}
