<?php
namespace SosyalliftAIPro\Core;

class Activator {
    public static function activate(): void {
        // Requirements kontrolü
        if (!Requirements::check()) {
            wp_die('Sosyallift AI Pro sistem gereksinimlerini karşılamıyor. Lütfen gereksinimleri kontrol edin.');
        }
        
        // Veritabanı tablolarını oluştur
        Installer::install();
        
        // Default options
        $defaults = [
            'sl_ai_pro_version' => SL_AI_PRO_VERSION,
            'sl_ai_pro_installed_at' => current_time('mysql'),
            'sl_ai_pro_currency' => 'TRY',
            'sl_ai_pro_timezone' => wp_timezone_string(),
            'sl_ai_pro_auto_sync' => true,
            'sl_ai_pro_sync_interval' => 300,
            'sl_ai_pro_cache_ttl' => 300,
            'sl_ai_pro_debug_mode' => false,
        ];
        
        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
        
        // Cron job'ları schedule et
        self::schedule_cron_jobs();
        
        // Lisans ping gönder
        self::send_activation_ping();
        
        // Geçişleri çalıştır
        self::run_migrations();
    }
    
    private static function schedule_cron_jobs(): void {
        if (!wp_next_scheduled('sl_ai_pro_cron_sync')) {
            wp_schedule_event(time(), 'sl_ai_pro_5min', 'sl_ai_pro_cron_sync');
        }
        
        if (!wp_next_scheduled('sl_ai_pro_cron_cleanup')) {
            wp_schedule_event(time(), 'daily', 'sl_ai_pro_cron_cleanup');
        }
        
        if (!wp_next_scheduled('sl_ai_pro_cron_reporting')) {
            wp_schedule_event(time(), 'daily', 'sl_ai_pro_cron_reporting');
        }
    }
    
    private static function send_activation_ping(): void {
        $data = [
            'domain' => site_url(),
            'version' => SL_AI_PRO_VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'action' => 'activate'
        ];
        
        wp_remote_post('https://sosyallift.com/api/v1/plugin/ping', [
            'body' => $data,
            'timeout' => 5,
            'blocking' => false
        ]);
    }
    
    private static function run_migrations(): void {
        $migration_manager = new MigrationManager();
        $migration_manager->run();
    }
}