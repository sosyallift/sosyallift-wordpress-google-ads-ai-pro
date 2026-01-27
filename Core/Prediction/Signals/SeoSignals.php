<?php
namespace SosyalliftAIPro\Core\Prediction\Signals;

class SeoSignals {

    public static function collect(): array {
        return [
            'queries'        => get_transient('sl_ai_pro_serp_queries') ?: [],
            'avg_position'   => get_option('sl_ai_pro_avg_position'),
            'click_through'  => get_option('sl_ai_pro_ctr'),
            'anomaly_flag'   => get_option('sl_ai_pro_serp_anomaly'),
        ];
    }
}
