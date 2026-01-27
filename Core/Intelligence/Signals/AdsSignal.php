<?php
namespace SosyalliftAIPro\Core\Intelligence\Signals;

defined('ABSPATH') || exit;

class AdsSignal {

    public static function analyze(array $ads): array {

        $clicks = (int) ($ads['clicks'] ?? 0);

        return [
            'clicks' => $clicks,
            'active' => $clicks > 0,
            'signal_strength' => match (true) {
                $clicks === 0   => 0,
                $clicks < 10    => 30,
                $clicks < 50    => 60,
                default         => 90,
            },
        ];
    }
}
