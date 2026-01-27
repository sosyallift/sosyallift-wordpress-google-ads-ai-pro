<?php
namespace SosyalliftAIPro\Core\Intelligence;

use SosyalliftAIPro\Core\Intelligence\Anomaly\AnomalyScorer;
use SosyalliftAIPro\Core\Intelligence\Anomaly\AnomalyExplainer;

defined('ABSPATH') || exit;

class IntelligenceKernel {

    public static function run(array $dashboardPayload): IntelligenceResult {

        $context = new IntelligenceContext($dashboardPayload);

        $score     = AnomalyScorer::score($context);
        $explain   = AnomalyExplainer::explain($context, $score);

        return new IntelligenceResult(
            $score,
            $explain,
            self::confidence($context)
        );
    }

    private static function confidence(IntelligenceContext $context): int {
        $signals = count(array_filter([
            $context->traffic,
            $context->ads,
            $context->seo,
            $context->intent,
        ]));

        return min(100, $signals * 25);
    }
}
