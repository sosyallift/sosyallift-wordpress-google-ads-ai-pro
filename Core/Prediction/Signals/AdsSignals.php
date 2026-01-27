<?php
namespace SosyalliftAIPro\Core\Prediction\Signals;

class AdsSignals {

    public static function collect(): array {
        return [
            'keywords'       => get_transient('sl_ai_pro_ads_keywords') ?: [],
            'devices'        => get_option('sl_ai_pro_ads_devices'),
            'time_patterns'  => get_option('sl_ai_pro_ads_time_patterns'),
            'anomaly_score'  => get_option('sl_ai_pro_ads_anomaly_score'),
        ];
    }
}
