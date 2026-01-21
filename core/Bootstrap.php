<?php
namespace SL_AI\Core;
class Bootstrap {
    public static function init() {
        // Admin sayfalarını yükle
        if (is_admin()) {
            add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
            add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        }
        // AJAX handler'ları
        self::register_ajax_handlers();
        // Shortcode
        add_shortcode('sl_ai_stats', [__CLASS__, 'shortcode_stats']);
    }
    public static function add_admin_menu() {
        add_menu_page(
            'AI Intelligence',
            'AI Intelligence',
            'manage_options',
            'sl-ai-dashboard',
            [__CLASS__, 'render_dashboard'],
            'dashicons-chart-line',
            30
        );
        // Alt menüler
        add_submenu_page(
            'sl-ai-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sl-ai-dashboard',
            [__CLASS__, 'render_dashboard']
        );
        add_submenu_page(
            'sl-ai-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sl-ai-settings',
            [__CLASS__, 'render_settings']
        );
    }
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sl-ai') === false) return;
        wp_enqueue_style(
            'sl-ai-admin',
            SL_AI_URL . 'assets/css/admin.css',
            [],
            SL_AI_VERSION
        );
        wp_enqueue_script(
            'sl-ai-admin',
            SL_AI_URL . 'assets/js/admin.js',
            ['jquery'],
            SL_AI_VERSION,
            true
        );
    }
    public static function register_ajax_handlers() {
        $actions = [
            'get_stats',
            'run_analysis',
            'export_data'
        ];
        foreach ($actions as $action) {
            add_action("wp_ajax_sl_ai_$action", [__CLASS__, "ajax_$action"]);
        }
    }
    public static function render_dashboard() {
        include SL_AI_PATH . 'admin/views/dashboard.php';
    }
    public static function render_settings() {
        include SL_AI_PATH . 'admin/views/settings.php';
    }
    public static function shortcode_stats($atts) {
        $atts = shortcode_atts([
            'type' => 'basic',
            'limit' => 5
        ], $atts);
        ob_start();
        include SL_AI_PATH . 'templates/shortcode-stats.php';
        return ob_get_clean();
    }
    public static function ajax_get_stats() {
        check_ajax_referer('sl_ai_ajax', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $data = [
            'total_clicks' => 1500,
            'conversions' => 45,
            'ctr' => 3.2,
            'roas' => 4.5
        ];
        wp_send_json_success($data);
    }
}
