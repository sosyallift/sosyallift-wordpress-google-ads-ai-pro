<?php
namespace SosyalliftAIPro\Core\AI;

use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

final class AIBridge {

    public static function request(AIRequest $request): ?AIResponse {

        $endpoint = get_option('sosyallift_ai_endpoint');
        $token    = get_option('sosyallift_agent_token');

        // local-first: AI yoksa sessizce geç
        if (!$endpoint) {
            return null;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 5,
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-Agent-Token' => $token,
            ],
            'body' => wp_json_encode($request->toArray()),
        ]);

        Logger::get_instance()->log(
            LogTypes::AI,
            'AIBridge',
            'AI request sent',
            ['endpoint' => $endpoint]
        );

        if (is_wp_error($response)) {
            Logger::get_instance()->log(
                LogTypes::AI,
                'AIBridge',
                'AI request failed',
                ['error' => $response->get_error_message()]
            );
            return null;
        }

        return new AIResponse(
            json_decode(wp_remote_retrieve_body($response), true)
        );
    }
}
