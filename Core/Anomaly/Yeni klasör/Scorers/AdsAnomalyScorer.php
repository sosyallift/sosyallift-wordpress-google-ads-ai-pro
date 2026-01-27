<?php
namespace SosyalliftAIPro\Core\Anomaly\Scorers;

use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

defined('ABSPATH') || exit;

class AdsAnomalyScorer {

    public static function score(array $ads, AnomalyResult $result): void {

        if (($ads['ctr'] ?? 0) < 0.01 && ($ads['clicks'] ?? 0) > 100) {
            $result->add(
                'ads',
                'High clicks but extremely low CTR',
                3,
                $ads
            );
        }
    }
}
