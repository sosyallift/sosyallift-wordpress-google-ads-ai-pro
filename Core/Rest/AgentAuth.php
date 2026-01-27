<?php
namespace SosyalliftAIPro\Core\Rest;

class AgentAuth {

    public static function verify(string $token): bool {
        return hash_equals(
            get_option('sosyallift_agent_token'),
            $token
        );
    }
}
