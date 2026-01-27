<?php
namespace SosyalliftAIPro\Core\Anomaly\Contracts;

use SosyalliftAIPro\Core\Anomaly\AnomalyResult;

interface ScorerInterface {
    public static function score(array $payload, AnomalyResult $result): void;
}
