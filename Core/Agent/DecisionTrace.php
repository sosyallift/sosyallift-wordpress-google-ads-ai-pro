<?php
namespace SosyalliftAIPro\Core\Agent;

use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

class DecisionTrace {

    public static function record(
        string $decision,
        array $signals,
        string $result
    ): void {
        Logger::get_instance()->log(
            LogTypes::AGENT,
            'DecisionTrace',
            'Agent decision made',
            [
                'decision' => $decision,
                'signals'  => $signals,
                'result'   => $result
            ]
        );
    }
}
