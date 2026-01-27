<?php
namespace SosyalliftAIPro\Core\Security;

class CapabilityMap {

    public static function can(string $action): bool {

        $map = [
            'view_dashboard' => 'manage_options',
            'export_logs'    => 'manage_options',
            'agent_execute' => 'manage_network_options',
        ];

        return isset($map[$action]) && current_user_can($map[$action]);
    }
}
