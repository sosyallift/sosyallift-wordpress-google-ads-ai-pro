<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Serp;

use SosyalliftAIPro\Core\Anomaly\Contracts\ScorerInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class SerpScorer implements ScorerInterface {

    public static function score(array $payload, AnomalyResult $result): void {

        $metrics = SerpMetrics::extract($payload);

        SerpRules::apply($metrics, $result);

        $result->addSignal('serp', 'metrics', $metrics);
    }
}
