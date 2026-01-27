<?php
namespace SosyalliftAIPro\Core\Admin;

defined('ABSPATH') || exit;

class Menu {

    const CAPABILITY    = 'manage_options';
    const SLUG          = 'sl-ai-pro';
    const NETWORK_SLUG  = 'sl-ai-pro-network';

    public static function init(): void {

        add_action('admin_menu', [__CLASS__, 'register_single_site_menu']);

        if (is_multisite()) {
            add_action('network_admin_menu', [__CLASS__, 'register_network_menu']);
        }
    }

    public static function register_single_site_menu(): void {

        add_menu_page(
            'Sosyallift AI Pro',
            'Sosyallift AI',
            self::CAPABILITY,
            self::SLUG,
            [__CLASS__, 'render_dashboard'],
            'dashicons-chart-area',
            56
        );

        add_submenu_page(
            self::SLUG,
            'Dashboard',
            'Dashboard',
            self::CAPABILITY,
            self::SLUG,
            [__CLASS__, 'render_dashboard']
        );

        add_submenu_page(
            self::SLUG,
            'Raw Metrics',
            'Raw Metrics (Debug)',
            self::CAPABILITY,
            'sl-ai-pro-raw',
            [__CLASS__, 'render_raw_metrics']
        );

        add_submenu_page(
            self::SLUG,
            'API Ayarları',
            'API Ayarları',
            self::CAPABILITY,
            'sl-ai-pro-api',
            [__CLASS__, 'render_api_settings']
        );

        add_submenu_page(
            self::SLUG,
            'Genel Ayarlar',
            'Ayarlar',
            self::CAPABILITY,
            'sl-ai-pro-settings',
            [__CLASS__, 'render_settings']
        );
    }

    public static function register_network_menu(): void {

        add_menu_page(
            'Sosyallift AI Pro (Network)',
            'Sosyallift AI',
            'manage_network_options',
            self::NETWORK_SLUG,
            [__CLASS__, 'render_network_dashboard'],
            'dashicons-admin-network',
            56
        );
    }

    /* ================= RENDER ================= */

    public static function render_dashboard(): void {
        (new \SosyalliftAIPro\Admin\DashboardController())->render();
    }

    public static function render_raw_metrics(): void {
        include SL_AI_PRO_PATH . 'Admin/views/dashboard-raw.php';
    }

    public static function render_api_settings(): void {
        include SL_AI_PRO_PATH . 'Admin/views/api-settings.php';
    }

    public static function render_settings(): void {
        include SL_AI_PRO_PATH . 'Admin/views/settings.php';
    }

    public static function render_network_dashboard(): void {
        include SL_AI_PRO_PATH . 'Admin/views/network-dashboard.php';
    }
}
