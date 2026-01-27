<?php
namespace SosyalliftAIPro\Core\Intelligence\Signals;

defined('ABSPATH') || exit;

class TrafficSignal {

    public static function analyze(array $traffic): array {

        $visitors = (int) ($traffic['visitors'] ?? 0);

        return [
            'visitors' => $visitors,
            'level' => match (true) {
                $visitors === 0        => 'none',
                $visitors < 50         => 'low',
                $visitors < 300        => 'medium',
                default                => 'high',
            },
            'signal_strength' => min(100, $visitors),
        ];
    }
}
