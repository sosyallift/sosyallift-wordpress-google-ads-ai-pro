<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Behavior;

use SosyalliftAIPro\Core\Anomaly\Contracts\RuleInterface;
use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

final class BehaviorRules implements RuleInterface {

    public static function apply(array $m, AnomalyResult $r): void {

        if ($m['bounce_rate'] > 85 && $m['time_on_site'] < 10) {
            $r->addScore('behavior', 35);
            $r->addSignal('behavior', 'high_bounce_fast_exit', true);
        }

        if ($m['pages'] <= 1 && $m['time_on_site'] < 15) {
            $r->addScore('behavior', 20);
            $r->addSignal('behavior', 'no_engagement', true);
        }
    }
}
