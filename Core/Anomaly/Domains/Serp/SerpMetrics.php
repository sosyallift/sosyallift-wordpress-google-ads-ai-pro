<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Serp;

use SosyalliftAIPro\Core\Anomaly\Contracts\MetricInterface;

final class SerpMetrics implements MetricInterface {

    public static function extract(array $payload): array {
        return [
            'avg_position' => (float)($payload['avg_position'] ?? 0),
            'clicks'       => (int)($payload['clicks'] ?? 0),
            'impressions'  => (int)($payload['impressions'] ?? 0),
        ];
    }
}
