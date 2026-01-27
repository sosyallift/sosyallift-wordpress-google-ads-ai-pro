<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Ads;

use SosyalliftAIPro\Core\Anomaly\Contracts\ScorerInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class AdsScorer implements ScorerInterface {

    public static function score(array $payload, AnomalyResult $result): void {

        $metrics = AdsMetrics::extract($payload);

        AdsRules::apply($metrics, $result);

        $result->addSignal('ads', 'metrics', $metrics);
    }
}
