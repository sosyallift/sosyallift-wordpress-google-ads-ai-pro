<?php
namespace SosyalliftAIPro\core;

class Requirements {
    public static function check(): bool {
        $errors = [];
        
        // PHP versiyon kontrolü
        if (version_compare(PHP_VERSION, SL_AI_PRO_MIN_PHP, '<')) {
            $errors[] = sprintf(
                'PHP %s veya üstü gereklidir. Mevcut: %s',
                SL_AI_PRO_MIN_PHP,
                PHP_VERSION
            );
        }
        
        // WordPress versiyon kontrolü
        global $wp_version;
        if (version_compare($wp_version, SL_AI_PRO_MIN_WP, '<')) {
            $errors[] = sprintf(
                'WordPress %s veya üstü gereklidir. Mevcut: %s',
                SL_AI_PRO_MIN_WP,
                $wp_version
            );
        }
        
        // Gerekli PHP extension'ları
        $required_extensions = [
            'curl',
            'json',
            'mbstring',
            'xml',
            'openssl',
            'pdo_mysql'
        ];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = sprintf('PHP %s extension gereklidir', $ext);
            }
        }
        
        // MySQL versiyon kontrolü
        global $wpdb;
        $mysql_version = $wpdb->db_version();
        if (version_compare($mysql_version, '5.6', '<')) {
            $errors[] = sprintf('MySQL 5.6 veya üstü gereklidir. Mevcut: %s', $mysql_version);
        }
        
        // Memory limit kontrolü
        $memory_limit = ini_get('memory_limit');
        $min_memory = 134217728; // 128MB
        if (wp_convert_hr_to_bytes($memory_limit) < $min_memory) {
            $errors[] = sprintf('Minimum 128MB memory limit gereklidir. Mevcut: %s', $memory_limit);
        }
        
        if (!empty($errors)) {
            update_option('sl_ai_pro_requirements_errors', $errors);
            return false;
        }
        
        delete_option('sl_ai_pro_requirements_errors');
        return true;
    }
    
    public static function show_notice(): void {
        $errors = get_option('sl_ai_pro_requirements_errors', []);
        if (empty($errors)) {
            return;
        }
        
        $message = '<strong>Sosyallift AI Pro kurulumu başarısız:</strong><br>';
        $message .= implode('<br>', $errors);
        
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            $message
        );
    }
}