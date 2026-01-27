<?php
namespace SosyalliftAIPro\Core\Prediction;

class PredictionContext {

    public static function build(): array {
        return [
            'site_id'   => get_current_blog_id(),
            'time'      => time(),
            'seo'       => \SosyalliftAIPro\Core\Prediction\Signals\SeoSignals::collect(),
            'ads'       => \SosyalliftAIPro\Core\Prediction\Signals\AdsSignals::collect(),
            'behavior'  => \SosyalliftAIPro\Core\Prediction\Signals\BehaviorSignals::collect(),
        ];
    }
}
