<?php
namespace SosyalliftAIPro\Admin;

use SosyalliftAIPro\Includes\Traits\Singleton;
use SosyalliftAIPro\Core\Logger;

class Admin {
    use Singleton;
    
    private $pages = [];
    
    protected function __construct() {
        // Boş constructor
    }
    
    public function init(): void {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        
        Logger::info('Admin modülü başlatıldı');
    }
    
    public function register_menus(): void {
        // Ana menü
        add_menu_page(
            'AI Intelligence Dashboard',
            'AI Intelligence',
            'manage_options',
            'sl_ai_pro_dashboard',
            [$this, 'render_dashboard'],
            'dashicons-chart-line',
            30
        );
        
        // Alt menüler
        $submenus = [
            [
                'title' => 'Dashboard',
                'slug'  => 'sl_ai_pro_dashboard',
                'callback' => [$this, 'render_dashboard'],
            ],
            [
                'title' => 'Google Ads',
                'slug'  => 'sl_ai_pro_google_ads',
                'callback' => [$this, 'render_google_ads'],
            ],
            [
                'title' => 'SEO Analiz',
                'slug'  => 'sl_ai_pro_seo',
                'callback' => [$this, 'render_seo'],
            ],
            [
                'title' => 'Intent Analiz',
                'slug'  => 'sl_ai_pro_intent',
                'callback' => [$this, 'render_intent'],
            ],
            [
                'title' => 'AI Intelligence',
                'slug'  => 'sl_ai_pro_intelligence',
                'callback' => [$this, 'render_intelligence'],
            ],
            [
                'title' => 'Ayarlar',
                'slug'  => 'sl_ai_pro_settings',
                'callback' => [$this, 'render_settings'],
            ],
        ];
        
        foreach ($submenus as $menu) {
            add_submenu_page(
                'sl_ai_pro_dashboard',
                $menu['title'],
                $menu['title'],
                'manage_options',
                $menu['slug'],
                $menu['callback']
            );
        }
    }
    
    public function render_dashboard(): void {
        echo '<div class="wrap">';
        echo '<h1>AI Intelligence Dashboard</h1>';
        echo '<div class="card"><h3>Plugin Aktif!</h3><p>Tüm sistemler çalışıyor.</p></div>';
        echo '</div>';
    }
    
    public function render_google_ads(): void {
        echo '<div class="wrap"><h1>Google Ads Yönetimi</h1>';
        echo '<p>Google Ads entegrasyonu aktif.</p>';
        echo '</div>';
    }
    
    public function render_seo(): void {
        echo '<div class="wrap"><h1>SEO Analiz</h1>';
        echo '<p>SEO analiz modülü aktif.</p>';
        echo '</div>';
    }
    
    public function render_intent(): void {
        echo '<div class="wrap"><h1>Intent Analiz</h1>';
        echo '<p>Intent tespit modülü aktif.</p>';
        echo '</div>';
    }
    
    public function render_intelligence(): void {
        echo '<div class="wrap"><h1>AI Intelligence</h1>';
        echo '<p>AI öneri modülü aktif.</p>';
        echo '</div>';
    }
    
    public function render_settings(): void {
        echo '<div class="wrap"><h1>Ayarlar</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('sl_ai_pro_settings');
        do_settings_sections('sl_ai_pro_settings');
        submit_button();
        echo '</form></div>';
    }
    
    public function register_settings(): void {
        register_setting('sl_ai_pro_settings', 'sl_ai_pro_api_key');
        register_setting('sl_ai_pro_settings', 'sl_ai_pro_google_ads_settings');
        
        add_settings_section(
            'sl_ai_pro_api_section',
            'API Ayarları',
            [$this, 'render_api_section'],
            'sl_ai_pro_settings'
        );
        
        add_settings_field(
            'sl_ai_pro_api_key',
            'API Anahtarı',
            [$this, 'render_api_key_field'],
            'sl_ai_pro_settings',
            'sl_ai_pro_api_section'
        );
    }
    
    public function render_api_section(): void {
        echo '<p>API bağlantı ayarlarını yapılandırın.</p>';
    }
    
    public function render_api_key_field(): void {
        $api_key = get_option('sl_ai_pro_api_key', '');
        echo '<input type="password" name="sl_ai_pro_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    }
    
    public function enqueue_assets($hook): void {
        // Sadece bizim sayfalarımızda yükle
        if (strpos($hook, 'sl_ai_pro_') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sl-ai-pro-admin',
            SL_AI_PRO_URL . 'assets/css/admin.css',
            [],
            SL_AI_PRO_VERSION
        );
        
        wp_enqueue_script(
            'sl-ai-pro-admin',
            SL_AI_PRO_URL . 'assets/js/admin.js',
            ['jquery'],
            SL_AI_PRO_VERSION,
            true
        );
        
        wp_localize_script('sl-ai-pro-admin', 'sl_ai_pro', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sl_ai_pro_ajax'),
        ]);
    }
}