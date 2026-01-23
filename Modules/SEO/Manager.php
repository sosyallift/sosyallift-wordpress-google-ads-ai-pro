<?php
namespace SosyalliftAIPro\Modules\SEO;

use SosyalliftAIPro\Includes\Traits\Singleton;
use SosyalliftAIPro\Core\Logger;

class Manager {
    use Singleton;
    
    private $is_active = true;
    private $settings;
    
    protected function __construct() {
        $this->settings = get_option('sl_ai_pro_seo_settings', [
            'google_search_console' => '',
            'google_analytics' => '',
            'auto_audit' => true,
            'audit_interval' => 604800, // 1 hafta
        ]);
    }
    
    public function register(): void {
        if (!$this->is_active()) return;
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('sl_ai_pro_cron_sync', [$this, 'cron_sync']);
        Logger::info('SEO Modülü kaydedildi');
    }
    
    public function is_active(): bool {
        return $this->is_active;
    }
    
    public function add_admin_menu(): void {
        add_submenu_page(
            'sl_ai_pro_dashboard',
            'SEO Analiz',
            'SEO',
            'manage_options',
            'sl-ai-pro-seo',
            [$this, 'render_admin_page']
        );
    }
    
    public function render_admin_page(): void {
        echo '<div class="wrap"><h1>SEO Analiz Modülü</h1><p>SEO analiz ve takip özellikleri.</p></div>';
    }
    
    public function cron_sync(): void {
        Logger::debug('SEO cron sync çalıştı');
    }
    
    public function get_module_info(): array {
        return [
            'name' => 'SEO Analiz',
            'version' => '2.0.0',
            'active' => $this->is_active(),
        ];
    }
}