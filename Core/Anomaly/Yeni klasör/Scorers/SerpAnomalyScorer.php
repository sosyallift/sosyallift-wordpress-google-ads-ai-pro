<?php
namespace SosyalliftAIPro\Core\Anomaly\Scorers;

use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

defined('ABSPATH') || exit;

class SerpAnomalyScorer {

    public static function score(array $seo, AnomalyResult $result): void {

        if (($seo['avg_position'] ?? 0) < 3 && ($seo['ctr'] ?? 0) < 0.02) {
            $result->add(
                'seo',
                'Top SERP position but low CTR – intent mismatch',
                2,
                $seo
            );
        }
    }
}
