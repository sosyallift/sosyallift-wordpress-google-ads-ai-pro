<?php
namespace SosyalliftAIPro\Modules\Intent;

use SosyalliftAIPro\Includes\Traits\Singleton;
use SosyalliftAIPro\Core\Logger;

class Manager {
    use Singleton;
    
    private $is_active = true;
    private $settings;
    
    protected function __construct() {
        $this->settings = get_option('sl_ai_pro_intent_settings', [
            'ai_model' => 'gpt-3.5-turbo',
            'detection_threshold' => 0.7,
            'auto_detect' => true,
        ]);
    }
    
    public function register(): void {
        if (!$this->is_active()) return;
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('sl_ai_pro_cron_sync', [$this, 'cron_sync']);
        Logger::info('Intent Modülü kaydedildi');
    }
    
    public function is_active(): bool {
        return $this->is_active;
    }
    
    public function add_admin_menu(): void {
        add_submenu_page(
            'sl_ai_pro_dashboard',
            'Intent Analiz',
            'Intent',
            'manage_options',
            'sl-ai-pro-intent',
            [$this, 'render_admin_page']
        );
    }
    
    public function render_admin_page(): void {
        echo '<div class="wrap"><h1>Intent Analiz Modülü</h1><p>Kullanıcı niyeti tespit ve analiz özellikleri.</p></div>';
    }
    
    public function cron_sync(): void {
        Logger::debug('Intent cron sync çalıştı');
    }
    
    public function get_module_info(): array {
        return [
            'name' => 'Intent Analiz',
            'version' => '2.0.0',
            'active' => $this->is_active(),
        ];
    }
}