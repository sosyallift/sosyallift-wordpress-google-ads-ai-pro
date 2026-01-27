<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Behavior;

use SosyalliftAIPro\Core\Anomaly\Contracts\MetricInterface;

final class BehaviorMetrics implements MetricInterface {

    public static function extract(array $payload): array {
        return [
            'bounce_rate' => (float)($payload['bounce_rate'] ?? 0),
            'time_on_site'=> (int)($payload['time_on_site'] ?? 0),
            'pages'       => (int)($payload['pages'] ?? 0),
        ];
    }
}
