<?php
namespace SosyalliftAIPro\Core\Anomaly;

use SosyalliftAIPro\Core\Anomaly\Scorers\AdsAnomalyScorer;
use SosyalliftAIPro\Core\Anomaly\Scorers\SerpAnomalyScorer;
use SosyalliftAIPro\Core\Anomaly\Scorers\BehaviorAnomalyScorer;

defined('ABSPATH') || exit;

class AnomalyEngine {

    public static function analyze(array $payload): AnomalyResult {

        $result = new AnomalyResult();

        AdsAnomalyScorer::score($payload['ads'] ?? [], $result);
        SerpAnomalyScorer::score($payload['seo'] ?? [], $result);
        BehaviorAnomalyScorer::score($payload['behavior'] ?? [], $result);

        return $result->finalize();
    }
}
