<?php
namespace SosyalliftAIPro\Core\Agent;

class DecisionEngine {

    public function decide(array $signals): string {

        // şimdilik placeholder
        if ($signals['memory'] > 50_000_000) {
            return 'throttle';
        }

        return 'fetch_more_data';
    }
}
