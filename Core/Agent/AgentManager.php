<?php
namespace SosyalliftAIPro\Core\Agent;

use SosyalliftAIPro\Core\Prediction\PredictionEngine;
use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

class AgentManager {

    public static function evaluate(): void {

        $prediction = PredictionEngine::run();

        if ($prediction->status === 'anomaly') {

            do_action('sl_ai_pro_agent_action', $prediction);

            Logger::get_instance()->log(
                LogTypes::AGENT,
                'AgentManager',
                'Agent triggered actions',
                $prediction->to_array()
            );
        }
    }
}
