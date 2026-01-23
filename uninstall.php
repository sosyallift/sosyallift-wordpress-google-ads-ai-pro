<?php
/**
 * Sosyallift AI Pro - Uninstall Script
 * 
 * This file cleans up all plugin data when the plugin is uninstalled.
 * 
 * @package SosyalliftAIPro
 */

// Exit if accessed directly.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Prevent execution by users without proper permissions.
if (!current_user_can('activate_plugins')) {
    return;
}

// Load WordPress database class
global $wpdb;

// Set table prefix
$prefix = $wpdb->prefix;

// ==================== TABLO TEMİZLEME ====================

// Sosyallift AI Pro tabloları
$plugin_tables = [
    // Mevcut tablolarınız
    $prefix . 'sl_ai_logs',
    $prefix . 'sl_ai_keywords',
    $prefix . 'sl_ai_scores',
    
    // Repository'de görülen diğer olası tablolar
    $prefix . 'sl_ai_alerts',
    $prefix . 'sl_ai_campaigns',
    $prefix . 'sl_ai_conversions',
    $prefix . 'sl_ai_intent',
    $prefix . 'sl_ai_migrations',
    $prefix . 'sl_ai_pages',
    $prefix . 'sl_ai_seo_data',
    $prefix . 'sl_ai_analytics',
    $prefix . 'sl_ai_cron_jobs',
    $prefix . 'sl_ai_settings',
    $prefix . 'sl_ai_templates',
    
    // Actionscheduler tabloları (plugin tarafından oluşturulmuşsa)
    $prefix . 'actionscheduler_actions',
    $prefix . 'actionscheduler_claims',
    $prefix . 'actionscheduler_groups',
    $prefix . 'actionscheduler_logs',
];

foreach ($plugin_tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

// ==================== OPTIONS TEMİZLEME ====================

$options_to_delete = [
    // Temel options
    'sl_ai_installed',
    'sl_ai_settings',
    'sl_ai_license',
    'sl_ai_license_key',
    'sl_ai_license_status',
    'sl_ai_db_version',
    'sl_ai_version',
    
    // Ayarlar
    'sl_ai_google_api_key',
    'sl_ai_openai_api_key',
    'sl_ai_google_ads_api',
    'sl_ai_seo_settings',
    'sl_ai_cron_settings',
    
    // Cache ve geçici veriler
    'sl_ai_cache',
    'sl_ai_last_sync',
    'sl_ai_analytics_data',
    
    // Menü ve arayüz
    'sl_ai_admin_notices',
    'sl_ai_welcome_dismissed',
    
    // Repository pattern'lerine göre
    'ai_intelligence_ai_installed',
    'ai_intelligence_ai_settings',
    'ai_intelligence_ai_license',
    'ai_intelligence_ai_api_keys',
    
    // Dinamik menü slug'ları
    'sl_ai_menu_slug',
    'sl_ai_active_menu',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option); // Multisite için
}

// ==================== TRANSIENT TEMİZLEME ====================

$transient_patterns = [
    '%sl_ai_%',
    '%sosyallift_%',
    '%ai_intelligence_%',
    '%ai_pro_%',
    '%google_ads_ai%',
];

foreach ($transient_patterns as $pattern) {
    // Normal transient'ler
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            '_transient_' . $pattern,
            '_transient_timeout_' . $pattern
        )
    );
    
    // Site transient'leri (multisite)
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            '_site_transient_' . $pattern,
            '_site_transient_timeout_' . $pattern
        )
    );
}

// ==================== CRON JOBS TEMİZLEME ====================

// Tüm plugin cron job'larını temizle
$cron_hooks = [
    'sl_ai_daily_sync',
    'sl_ai_hourly_check',
    'sl_ai_weekly_report',
    'sosyallift_ai_sync',
    'ai_intelligence_cron',
    'google_ads_ai_update',
];

foreach ($cron_hooks as $hook) {
    wp_clear_scheduled_hook($hook);
    
    // Tüm schedule'ları temizle
    $cron = get_option('cron');
    if (is_array($cron)) {
        foreach ($cron as $timestamp => $cron_array) {
            if (is_array($cron_array) && isset($cron_array[$hook])) {
                unset($cron[$timestamp][$hook]);
            }
        }
        update_option('cron', $cron);
    }
}

// ==================== USER META TEMİZLEME ====================

$user_meta_patterns = [
    'sl_ai_%',
    'sosyallift_%',
    'ai_intelligence_%',
];

foreach ($user_meta_patterns as $pattern) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} 
            WHERE meta_key LIKE %s",
            $pattern
        )
    );
}

// ==================== POST META TEMİZLEME ====================

$post_meta_patterns = [
    '_sl_ai_%',
    '_sosyallift_%',
    '_ai_intelligence_%',
    '_ai_seo_score',
    '_ai_intent_score',
];

foreach ($post_meta_patterns as $pattern) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE %s",
            $pattern
        )
    );
}

// ==================== TERM META TEMİZLEME ====================

$term_meta_patterns = [
    'sl_ai_%',
    'ai_intelligence_%',
];

foreach ($term_meta_patterns as $pattern) {
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->termmeta} 
            WHERE meta_key LIKE %s",
            $pattern
        )
    );
}

// ==================== UPLOADS KLASÖRÜ TEMİZLEME ====================

$upload_dir = wp_upload_dir();
$plugin_upload_dirs = [
    trailingslashit($upload_dir['basedir']) . 'sosyallift-ai/',
    trailingslashit($upload_dir['basedir']) . 'sl-ai-exports/',
    trailingslashit($upload_dir['basedir']) . 'ai-intelligence/',
];

foreach ($plugin_upload_dirs as $dir) {
    if (is_dir($dir)) {
        // Recursive directory removal
        $this->delete_directory($dir);
    }
}

// ==================== LOG DOSYALARI TEMİZLEME ====================

$log_files = [
    WP_CONTENT_DIR . '/debug.log',
    WP_CONTENT_DIR . '/sl-ai-debug.log',
    WP_CONTENT_DIR . '/sosyallift-errors.log',
];

foreach ($log_files as $log_file) {
    if (file_exists($log_file)) {
        // Sadece plugin ile ilgili satırları temizle
        $lines = file($log_file);
        $clean_lines = [];
        foreach ($lines as $line) {
            if (strpos($line, 'sl_ai') === false && 
                strpos($line, 'sosyallift') === false &&
                strpos($line, 'ai_intelligence') === false) {
                $clean_lines[] = $line;
            }
        }
        file_put_contents($log_file, implode('', $clean_lines));
    }
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Recursive directory deletion
 */
function delete_directory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            delete_directory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

// ==================== CACHE TEMİZLEME ====================

// WordPress object cache temizleme
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

// Popüler cache plugin'leri için
if (function_exists('rocket_clean_domain')) {
    rocket_clean_domain();
}

if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
}

if (function_exists('wpfc_clear_all_cache')) {
    wpfc_clear_all_cache();
}

// ==================== FINAL CLEANUP ====================

// Admin notice'ları temizle
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%dismissed_sl_ai_%'");

// Rewrite rules'ı yenile
flush_rewrite_rules();

// ==================== LOGGING (Opsiyonel) ====================

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Sosyallift AI Pro plugin successfully uninstalled and all data cleaned.');
}

// ==================== PLUGIN İÇİ SON TEMİZLİK ====================

// Eğer plugin kendi içinde başka cleanup fonksiyonları varsa
if (function_exists('sl_ai_cleanup_on_uninstall')) {
    sl_ai_cleanup_on_uninstall();
}

// Son olarak, eklentinin kendi version numarasını da sil
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '%' . $wpdb->esc_like('sosyallift') . '%'
    )
);

// Multisite için tüm bloglarda temizlik
if (is_multisite()) {
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
    $original_blog_id = get_current_blog_id();
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        
        // Her blog için options temizle
        foreach ($options_to_delete as $option) {
            delete_blog_option($blog_id, $option);
        }
        
        // Her blog için tabloları sil
        $blog_prefix = $wpdb->get_blog_prefix($blog_id);
        foreach ($plugin_tables as $table) {
            $blog_table = str_replace($prefix, $blog_prefix, $table);
            $wpdb->query("DROP TABLE IF EXISTS `{$blog_table}`");
        }
    }
    
    switch_to_blog($original_blog_id);
}

// Completion message
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Sosyallift AI Pro uninstall completed successfully.');
}