<?php
namespace SosyalliftAIPro\Core;

class Uninstaller {
    public static function uninstall(): void {
        // User capability kontrolü
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            exit;
        }
        
        // Admin olmayanlar için güvenlik
        if (!current_user_can('delete_plugins')) {
            wp_die('Bu işlemi gerçekleştirmek için yetkiniz yok.');
        }
        
        // Option'ları sil
        self::delete_options();
        
        // Tabloları sil
        self::delete_tables();
        
        // Upload dosyalarını sil
        self::delete_uploaded_files();
        
        // Cache dosyalarını sil
        self::delete_cache_files();
        
        // Log dosyalarını sil
        self::delete_log_files();
        
        // User meta'ları sil
        self::delete_user_meta();
    }
    
    private static function delete_options(): void {
        global $wpdb;
        
        $options = [
            'sl_ai_pro_%',
            'sl_ai_%' // Eski versiyon compatibility
        ];
        
        foreach ($options as $pattern) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $pattern
                )
            );
        }
    }
    
    private static function delete_tables(): void {
        global $wpdb;
        
        $tables = [
            "{$wpdb->prefix}sl_ai_logs",
            "{$wpdb->prefix}sl_ai_keywords",
            "{$wpdb->prefix}sl_ai_scores",
            "{$wpdb->prefix}sl_ai_intent",
            "{$wpdb->prefix}sl_ai_conversions",
            "{$wpdb->prefix}sl_ai_seo_data",
            "{$wpdb->prefix}sl_ai_pages",
            "{$wpdb->prefix}sl_ai_alerts",
            "{$wpdb->prefix}sl_ai_campaigns",
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    private static function delete_uploaded_files(): void {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/sosyallift-ai-pro/';
        
        if (file_exists($plugin_dir)) {
            self::delete_directory($plugin_dir);
        }
    }
    
    private static function delete_cache_files(): void {
        $cache_dir = WP_CONTENT_DIR . '/cache/sosyallift-ai-pro/';
        
        if (file_exists($cache_dir)) {
            self::delete_directory($cache_dir);
        }
    }
    
    private static function delete_log_files(): void {
        $log_dir = SL_AI_PRO_PATH . 'logs/';
        
        if (file_exists($log_dir)) {
            $files = glob($log_dir . '*.log');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    private static function delete_user_meta(): void {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
                'sl_ai_pro_%'
            )
        );
    }
    
    private static function delete_directory(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}