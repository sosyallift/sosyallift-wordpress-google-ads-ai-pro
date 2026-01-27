<?php
namespace SosyalliftAIPro\Admin;

use SosyalliftAIPro\Core\Dashboard\DataProvider;
use SosyalliftAIPro\Core\Intelligence\IntelligenceKernel;

defined('ABSPATH') || exit;

class DashboardController {

    public function render(): void {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        wp_enqueue_script(
            'sl-ai-dashboard',
            SL_AI_PRO_URL . 'assets/js/dashboard.js',
            ['jquery'],
            SL_AI_PRO_VERSION,
            true
        );

        wp_localize_script(
            'sl-ai-dashboard',
            'SL_AI',
            [
                'nonce' => wp_create_nonce('sl_ai_nonce'),
                'ajax'  => admin_url('admin-ajax.php'),
            ]
        );

        include SL_AI_PRO_PATH . 'Admin/views/dashboard.php';
    }
}
