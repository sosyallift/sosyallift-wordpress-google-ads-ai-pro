<?php
namespace SosyalliftAIPro\Core\Intelligence\Anomaly;

use SosyalliftAIPro\Core\Intelligence\IntelligenceContext;

defined('ABSPATH') || exit;

class AnomalyScorer {

    public static function score(IntelligenceContext $context): int {

        $score = 0;

        $score += self::trafficScore($context);
        $score += self::adsScore($context);
        $score += self::seoScore($context);
        $score += self::crossImpactScore($context);

        return min(100, max(0, $score));
    }

    /**
     * TRAFFIC anomalisi
     */
    private static function trafficScore(IntelligenceContext $context): int {

        $traffic = $context->traffic;

        if (empty($traffic['visitors'])) {
            return 15; // veri yoksa risk
        }

        if (
            isset($traffic['change_pct']) &&
            $traffic['change_pct'] < -30
        ) {
            return 25;
        }

        return 0;
    }

    /**
     * ADS anomalisi
     */
    private static function adsScore(IntelligenceContext $context): int {

        $ads = $context->ads;

        if (empty($ads['clicks']) || empty($ads['cost'])) {
            return 0;
        }

        if (
            isset($ads['conversion_rate']) &&
            $ads['conversion_rate'] < 0.5
        ) {
            return 20;
        }

        return 0;
    }

    /**
     * SEO anomalisi
     */
    private static function seoScore(IntelligenceContext $context): int {

        $seo = $context->seo;

        if (
            isset($seo['health']) &&
            $seo['health'] === 'critical'
        ) {
            return 20;
        }

        return 0;
    }

    /**
     * DOMAINLER ARASI ETKİ
     */
    private static function crossImpactScore(IntelligenceContext $context): int {

        // Ads var ama traffic artmıyorsa
        if (
            !empty($context->ads['clicks']) &&
            (
                empty($context->traffic['visitors']) ||
                ($context->traffic['change_pct'] ?? 0) < 0
            )
        ) {
            return 20;
        }

        return 0;
    }
}
