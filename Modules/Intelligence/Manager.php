<?php
namespace SosyalliftAIPro\Modules\Intelligence;

use SosyalliftAIPro\Includes\Traits\Singleton;
use SosyalliftAIPro\Core\Logger;

class Manager {
    use Singleton;
    
    private $is_active = true;
    private $settings;
    
    protected function __construct() {
        $this->settings = get_option('sl_ai_pro_intelligence_settings', [
            'ai_recommendations' => true,
            'predictive_analytics' => true,
            'alert_threshold' => 0.8,
        ]);
    }
    
    public function register(): void {
        if (!$this->is_active()) return;
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('sl_ai_pro_cron_sync', [$this, 'cron_sync']);
        Logger::info('Intelligence Modülü kaydedildi');
    }
    
    public function is_active(): bool {
        return $this->is_active;
    }
    
    public function add_admin_menu(): void {
        add_submenu_page(
            'sl_ai_pro_dashboard',
            'AI Intelligence',
            'Intelligence',
            'manage_options',
            'sl-ai-pro-intelligence',
            [$this, 'render_admin_page']
        );
    }
    
    public function render_admin_page(): void {
        echo '<div class="wrap"><h1>AI Intelligence Modülü</h1><p>AI destekli öneri ve analiz özellikleri.</p></div>';
    }
    
    public function cron_sync(): void {
        Logger::debug('Intelligence cron sync çalıştı');
    }
    
    public function get_module_info(): array {
        return [
            'name' => 'AI Intelligence',
            'version' => '2.0.0',
            'active' => $this->is_active(),
        ];
    }
}