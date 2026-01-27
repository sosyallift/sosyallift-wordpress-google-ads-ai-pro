<?php
namespace SosyalliftAIPro\Core\Rest;

defined('ABSPATH') || exit;

class RestBootstrap {

    public static function init() {

        AgentController::register_routes();
        StatsController::register_routes();
        LogsController::register_routes();

    }
}
