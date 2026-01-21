<?php
namespace SosyalliftAIPro\Core;

use SosyalliftAIPro\Core\Security;
use SosyalliftAIPro\Core\EventDispatcher;
use SosyalliftAIPro\Core\CronManager;
use SosyalliftAIPro\Core\CacheManager;
use SosyalliftAIPro\Core\LicenseManager;
use SosyalliftAIPro\Admin\Admin;
use SosyalliftAIPro\Api\RestApi;
use SosyalliftAIPro\Modules\Manager;

class Bootstrap {
    use \SosyalliftAIPro\Includes\Traits\Singleton;

    private $modules = [];
    private $services = [];

    protected function __construct() {
        $this->init_constants();
        $this->init_autoloader();
        $this->init_services();
        $this->init_modules();
        $this->init_hooks();
    }

    public static function init(): void {
        $instance = self::get_instance();
        $instance->run();
    }

    private function init_constants(): void {
        if (!defined('SL_AI_PRO_DEBUG')) {
            define('SL_AI_PRO_DEBUG', WP_DEBUG);
        }

        if (!defined('SL_AI_PRO_LOG_LEVEL')) {
            define('SL_AI_PRO_LOG_LEVEL', SL_AI_PRO_DEBUG ? 'debug' : 'error');
        }

        if (!defined('SL_AI_PRO_CRON_INTERVAL')) {
            define('SL_AI_PRO_CRON_INTERVAL', 300); // 5 minutes
        }
    }

    private function init_autoloader(): void {
        spl_autoload_register(function ($class) {
            $prefix = 'SosyalliftAIPro\\';
            $base_dir = SL_AI_PRO_PATH . 'src/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }

    private function init_services(): void {
        $this->services = [
            'security'      => Security::get_instance(),
            'cache'         => CacheManager::get_instance(),
            'events'        => EventDispatcher::get_instance(),
            'cron'          => CronManager::get_instance(),
            'license'       => LicenseManager::get_instance(),
            'logger'        => Logger::get_instance(),
            'validator'     => Validator::get_instance(),
            'api_client'    => ApiClient::get_instance(),
        ];

        foreach ($this->services as $service) {
            if (method_exists($service, 'init')) {
                $service->init();
            }
        }
    }

    private function init_modules(): void {
        $this->modules = [
            'google_ads'    => \SosyalliftAIPro\Modules\GoogleAds\Manager::get_instance(),
            'seo'           => \SosyalliftAIPro\Modules\SEO\Manager::get_instance(),
            'intent'        => \SosyalliftAIPro\Modules\Intent\Manager::get_instance(),
            'intelligence'  => \SosyalliftAIPro\Modules\Intelligence\Manager::get_instance(),
        ];

        // Register module dependencies
        add_filter('sl_ai_pro/modules/register', function ($modules) {
            return array_merge($modules, $this->modules);
        });

        // Initialize modules
        foreach ($this->modules as $module) {
            if ($module->is_active()) {
                $module->register();
            }
        }
    }

    private function init_hooks(): void {
        // Admin
        if (is_admin()) {
            Admin::get_instance()->init();
        }

        // REST API
        add_action('rest_api_init', [RestApi::class, 'register_routes']);

        // Cron jobs
        add_filter('cron_schedules', [$this->services['cron'], 'add_cron_intervals']);
        add_action('sl_ai_pro_cron_sync', [$this, 'cron_sync_handler']);
        add_action('sl_ai_pro_cron_cleanup', [$this, 'cron_cleanup_handler']);

        // AJAX handlers
        $this->register_ajax_handlers();

        // Shortcodes
        add_shortcode('sl_ai_stats', [$this, 'shortcode_stats']);

        // WP CLI
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('sl-ai-pro', SosyalliftAIPro\Cli\Kernel::class);
        }
    }

    private function register_ajax_handlers(): void {
        $ajax_actions = [
            'get_dashboard_data',
            'run_analysis',
            'export_report',
            'sync_data',
            'test_connection',
            'clear_cache',
            'send_test_alert',
            'regenerate_recommendations',
        ];

        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_sl_ai_pro_{$action}", [$this, "ajax_{$action}"]);
            add_action("wp_ajax_nopriv_sl_ai_pro_{$action}", [$this, 'ajax_nopriv_handler']);
        }
    }

    public function run(): void {
        // Verify license on every admin page load
        if (is_admin() && !wp_doing_ajax()) {
            $this->services['license']->verify_license_async();
        }

        // Schedule cron jobs if not scheduled
        if (!wp_next_scheduled('sl_ai_pro_cron_sync')) {
            wp_schedule_event(time(), 'sl_ai_pro_5min', 'sl_ai_pro_cron_sync');
        }

        // Fire init event
        $this->services['events']->dispatch('sl_ai_pro.init', [$this]);
    }

    public function cron_sync_handler(): void {
        $this->services['events']->dispatch('sl_ai_pro.cron.sync', []);
        
        foreach ($this->modules as $module) {
            if ($module->is_active()) {
                $module->cron_sync();
            }
        }
    }

    public function cron_cleanup_handler(): void {
        // Clean old logs
        Logger::cleanup_old_logs();
        
        // Clear expired cache
        $this->services['cache']->clean_expired();
        
        // Optimize database
        $this->optimize_database();
    }

    private function optimize_database(): void {
        global $wpdb;
        $tables = [
            "{$wpdb->prefix}sl_ai_keywords",
            "{$wpdb->prefix}sl_ai_logs",
            "{$wpdb->prefix}sl_ai_scores",
        ];

        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }

    public function ajax_get_dashboard_data(): void {
        check_ajax_referer('sl_ai_pro_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        $data = [
            'performance'   => $this->get_performance_data(),
            'keywords'      => $this->get_keywords_data(),
            'alerts'        => $this->get_alerts_data(),
            'insights'      => $this->get_insights_data(),
        ];

        wp_send_json_success($data);
    }

    private function get_performance_data(): array {
        global $wpdb;
        
        $cache_key = 'dashboard_performance_' . get_current_user_id();
        $cached = $this->services['cache']->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }

        $data = [
            'ads' => [
                'clicks'        => (int) $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}sl_ai_logs WHERE source = 'ads'"),
                'conversions'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_logs WHERE intent = 'commercial'"),
                'ctr'           => 0,
                'roas'          => 0,
            ],
            'seo' => [
                'impressions'   => (int) $wpdb->get_var("SELECT SUM(impressions) FROM {$wpdb->prefix}sl_ai_seo_data"),
                'clicks'        => (int) $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}sl_ai_seo_data"),
                'position'      => (float) $wpdb->get_var("SELECT AVG(position) FROM {$wpdb->prefix}sl_ai_seo_data"),
                'ctr'           => 0,
            ],
            'intent' => [
                'commercial'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_intent WHERE intent_type = 'commercial'"),
                'informational' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_intent WHERE intent_type = 'informational'"),
                'navigational'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_intent WHERE intent_type = 'navigational'"),
                'total'         => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sl_ai_intent"),
            ],
        ];

        // Calculate CTR
        if ($data['ads']['clicks'] > 0) {
            $impressions = (int) $wpdb->get_var("SELECT SUM(impressions) FROM {$wpdb->prefix}sl_ai_logs WHERE source = 'ads'");
            $data['ads']['ctr'] = $impressions > 0 ? round(($data['ads']['clicks'] / $impressions) * 100, 2) : 0;
        }

        // Cache for 5 minutes
        $this->services['cache']->set($cache_key, $data, 300);

        return $data;
    }

    public function shortcode_stats($atts): string {
        $atts = shortcode_atts([
            'type'      => 'overview',
            'limit'     => 5,
            'style'     => 'card',
            'refresh'   => false,
        ], $atts, 'sl_ai_stats');

        ob_start();
        include SL_AI_PRO_PATH . 'templates/shortcodes/stats-' . $atts['type'] . '.php';
        return ob_get_clean();
    }

    public function on_shutdown(): void {
        // Log any pending events
        Logger::flush();
        
        // Update last activity
        update_option('sl_ai_pro_last_activity', time());
    }
}
