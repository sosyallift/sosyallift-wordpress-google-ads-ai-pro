<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Behavior;

use SosyalliftAIPro\Core\Anomaly\Contracts\ScorerInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class BehaviorScorer implements ScorerInterface {

    public static function score(array $payload, AnomalyResult $result): void {

        $metrics = BehaviorMetrics::extract($payload);

        BehaviorRules::apply($metrics, $result);

        $result->addSignal('behavior', 'metrics', $metrics);
    }
}
