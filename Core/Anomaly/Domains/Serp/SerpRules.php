<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Serp;

use SosyalliftAIPro\Core\Anomaly\Contracts\RuleInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class SerpRules implements RuleInterface {

    public static function apply(array $m, AnomalyResult $r): void {

        if ($m['impressions'] > 1000 && $m['clicks'] < 5) {
            $r->addScore('serp', 30);
            $r->addSignal('serp', 'impression_no_click', true);
        }

        if ($m['avg_position'] > 20) {
            $r->addScore('serp', 15);
            $r->addSignal('serp', 'low_rank', $m['avg_position']);
        }
    }
}
