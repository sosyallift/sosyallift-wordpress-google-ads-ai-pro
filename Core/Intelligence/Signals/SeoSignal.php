<?php
namespace SosyalliftAIPro\Core\Intelligence\Signals;

defined('ABSPATH') || exit;

class SeoSignal {

    public static function analyze(array $seo): array {

        $health = $seo['health'] ?? 'unknown';

        return [
            'health' => $health,
            'degraded' => $health !== 'ok',
            'signal_strength' => match ($health) {
                'ok'        => 90,
                'warning'   => 50,
                'critical'  => 10,
                default     => 30,
            },
        ];
    }
}
