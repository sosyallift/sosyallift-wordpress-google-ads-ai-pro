<?php
namespace SL_AI\Core;

class Rest {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        register_rest_route('sl-ai/v1', '/analyze', [
            'methods' => 'POST',
            'callback' => [$this, 'analyze_keyword'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }
    
    private function check_permission($request) {
        return current_user_can('manage_options');
    }
}