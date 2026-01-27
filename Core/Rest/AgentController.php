<?php
namespace SosyalliftAIPro\Core\Rest;

use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

class AgentController {

    public static function register_routes() {

        register_rest_route('sosyallift/v1', '/agent/run', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'run'],
            'permission_callback' => [Permissions::class, 'internal_agent'],
        ]);
    }

    public static function run(WP_REST_Request $request) {

        $task = sanitize_text_field($request->get_param('task'));

        if (!$task) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => 'No task defined'
            ], 400);
        }

        /**
         * Agent dispatcher (future AI burada)
         */
        do_action('sosyallift_agent_run', $task, $request->get_params());

        return new WP_REST_Response([
            'status' => 'ok',
            'task'   => $task,
            'time'   => time()
        ]);
    }
}
