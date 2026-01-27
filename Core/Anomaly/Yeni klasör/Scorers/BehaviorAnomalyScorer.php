<?php
namespace SosyalliftAIPro\Core\Anomaly\Scorers;

use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

defined('ABSPATH') || exit;

class BehaviorAnomalyScorer {

    public static function score(array $behavior, AnomalyResult $result): void {

        if (($behavior['time_on_site'] ?? 0) < 5 && ($behavior['from_ads'] ?? false)) {
            $result->add(
                'behavior',
                'Ad traffic with instant bounce – possible mismatch or fraud',
                4,
                $behavior
            );
        }
    }
}
