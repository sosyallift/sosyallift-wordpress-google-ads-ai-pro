<?php
namespace SosyalliftAIPro\Core;

use SosyalliftAIPro\Core\Security;
use SosyalliftAIPro\Core\EventDispatcher;
use SosyalliftAIPro\Core\CronManager;
use SosyalliftAIPro\Core\CacheManager;
use SosyalliftAIPro\Core\LicenseManager;
use SosyalliftAIPro\Admin\Admin;
use SosyalliftAIPro\Api\RestApi;
use SosyalliftAIPro\Core\Admin\Menu;
use SosyalliftAIPro\Core\Admin\AjaxController;
use SosyalliftAIPro\Core\Rest\StatsEndpoint;
use SosyalliftAIPro\Core\Dashboard\DataProvider;
use SosyalliftAIPro\Core\Intelligence\IntelligenceKernel;

defined('ABSPATH') || exit;

class Bootstrap {
    use \SosyalliftAIPro\Includes\Traits\Singleton;

    /** @var array<string,object> */
    private array $services = [];

    /** @var array<string,mixed> */
    private array $modules  = [];

    protected function __construct() {
        $this->init_constants();
        $this->init_autoloader();
        $this->init_services();
        $this->init_modules();
        $this->init_hooks();
    }

    public static function init(): void {
        self::get_instance()->run();
    }

    /* =====================================================
     * INIT
     * ===================================================== */

    private function init_constants(): void {
        // mevcut yapın varsa DOKUNMUYORUM
    }

    private function init_autoloader(): void {
        // mevcut yapın varsa DOKUNMUYORUM
    }

    private function init_services(): void {

        $this->services['events']  = new EventDispatcher();
        $this->services['cron']    = new CronManager();
        $this->services['cache']   = new CacheManager();
        $this->services['license'] = new LicenseManager();

        /**
         * 🔒 DRY-RUN / LIVE MODE
         * default = DRY-RUN (canlıya zarar yok)
         */
        if (get_option('sl_ai_live_mode') === false) {
            add_option('sl_ai_live_mode', 0);
        }
    }

    private function init_modules(): void {
        // gelecekte AI, SaaS, Optimization modülleri buraya girer
    }

    /* =====================================================
     * HOOKS
     * ===================================================== */

    private function init_hooks(): void {

        /* ================= ADMIN ================= */
        if (is_admin()) {

            Admin::get_instance()->init();

            // Menü
            Menu::init();

            // AJAX Controller
            AjaxController::init();
        }

        /**
         * 🔥 INTELLIGENCE AJAX
         * Dashboard → IntelligenceKernel
         */
        add_action('wp_ajax_sl_ai_intelligence', function () {

            check_ajax_referer('sl_ai_nonce');

            $payload = DataProvider::get_dashboard_payload();
            $result  = IntelligenceKernel::run($payload);

            wp_send_json_success($result->toArray());
        });

        /* ================= REST ================= */

        add_action('rest_api_init', [RestApi::class, 'register_routes']);
        add_action('rest_api_init', [StatsEndpoint::class, 'register']);

        add_action('rest_api_init', [
            \SosyalliftAIPro\Core\Rest\RestBootstrap::class,
            'init'
        ]);

        /* ================= CRON ================= */

        add_filter('cron_schedules', [$this->services['cron'], 'add_cron_intervals']);

        add_action('sl_ai_pro_cron_sync', function () {
            $this->services['events']->dispatch('sl_ai.cron.sync');
        });

        add_action('sl_ai_pro_cron_cleanup', function () {
            $this->services['events']->dispatch('sl_ai.cron.cleanup');
        });
    }

    /* =====================================================
     * RUN
     * ===================================================== */

    public function run(): void {

        // Lisans async doğrulama
        if (is_admin() && !wp_doing_ajax()) {
            $this->services['license']->verify_license_async();
        }

        // Cron garantisi
        if (!wp_next_scheduled('sl_ai_pro_cron_sync')) {
            wp_schedule_event(time(), 'sl_ai_pro_5min', 'sl_ai_pro_cron_sync');
        }

        // Sistem ayağa kalktı sinyali
        $this->services['events']->dispatch('sl_ai.init', [
            'version' => defined('SL_AI_PRO_VERSION') ? SL_AI_PRO_VERSION : 'dev',
            'mode'    => get_option('sl_ai_live_mode') ? 'live' : 'dry-run',
        ]);
    }
}
