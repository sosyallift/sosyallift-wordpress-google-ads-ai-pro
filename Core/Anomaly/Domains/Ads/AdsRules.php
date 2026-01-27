<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Ads;

use SosyalliftAIPro\Core\Anomaly\Contracts\RuleInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class AdsRules implements RuleInterface {

    public static function apply(array $m, AnomalyResult $r): void {

        if ($m['spend'] > 0 && $m['conversions'] === 0) {
            $r->addScore('ads', 40);
            $r->addSignal('ads', 'budget_burn', true);
        }

        if ($m['ctr'] < 0.3 && $m['impressions'] > 1000) {
            $r->addScore('ads', 25);
            $r->addSignal('ads', 'low_ctr', $m['ctr']);
        }

        if ($m['cpc'] > 2.5 && $m['ctr'] < 0.5) {
            $r->addScore('ads', 20);
            $r->addSignal('ads', 'inefficient_cpc', $m['cpc']);
        }
    }
}
