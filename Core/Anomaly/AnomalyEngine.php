<?php
namespace SosyalliftAIPro\Core\Anomaly;

use SosyalliftAIPro\Core\Anomaly\Domains\Ads\AdsScorer;
use SosyalliftAIPro\Core\Anomaly\Domains\Serp\SerpScorer;
use SosyalliftAIPro\Core\Anomaly\Domains\Behavior\BehaviorScorer;

final class AnomalyEngine {

    public static function analyze(array $payload): AnomalyResult {

        $result = new AnomalyResult();

        AdsScorer::score($payload['ads'] ?? [], $result);
        SerpScorer::score($payload['serp'] ?? [], $result);
        BehaviorScorer::score($payload['behavior'] ?? [], $result);

        return $result->finalize();
    }
}
