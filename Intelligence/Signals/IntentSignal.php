<?php
namespace SosyalliftAIPro\Core\Intelligence\Signals;

defined('ABSPATH') || exit;

class IntentSignal {

    public static function analyze(array $intent): array {

        $commercial = (bool) ($intent['commercial'] ?? false);
        $confidence = (int) ($intent['confidence'] ?? 0);

        return [
            'commercial' => $commercial,
            'confidence' => $confidence,
            'signal_strength' => match (true) {
                $commercial && $confidence > 70 => 90,
                $commercial                     => 60,
                $confidence > 50                => 40,
                default                         => 10,
            },
        ];
    }
}
