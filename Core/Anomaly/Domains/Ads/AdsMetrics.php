<?php
namespace SosyalliftAIPro\Core\Anomaly\Domains\Ads;

use SosyalliftAIPro\Core\Anomaly\Contracts\MetricInterface;

final class AdsMetrics implements MetricInterface {

    public static function extract(array $payload): array {
        return [
            'impressions'  => (int)($payload['impressions'] ?? 0),
            'clicks'       => (int)($payload['clicks'] ?? 0),
            'ctr'          => (float)($payload['ctr'] ?? 0),
            'cpc'          => (float)($payload['cpc'] ?? 0),
            'spend'        => (float)($payload['spend'] ?? 0),
            'conversions'  => (int)($payload['conversions'] ?? 0),
        ];
    }
}
