<?php
namespace SosyalliftAIPro\Core\Anomaly\Contracts;

use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

interface RuleInterface {
    public static function apply(array $metrics, AnomalyResult $result): void;
}
