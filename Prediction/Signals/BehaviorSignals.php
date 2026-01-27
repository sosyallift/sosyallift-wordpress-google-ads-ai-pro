<?php
namespace SosyalliftAIPro\Core\Prediction\Signals;

class BehaviorSignals {

    public static function collect(): array {
        return [
            'entry_sources' => get_option('sl_ai_pro_entry_sources'),
            'intent_score'  => get_option('sl_ai_pro_intent_score'),
            'bounce_trace'  => get_option('sl_ai_pro_bounce_pattern'),
        ];
    }
}
