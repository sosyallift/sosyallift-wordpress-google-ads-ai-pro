<?php
namespace SosyalliftAIPro\Core;

class Deactivator {
    public static function deactivate(): void {
        // Cron job'ları temizle
        self::clear_cron_jobs();
        
        // Cache'i temizle
        self::clear_cache();
        
        // Deactivation ping gönder
        self::send_deactivation_ping();
    }
    
    private static function clear_cron_jobs(): void {
        $crons = [
            'sl_ai_pro_cron_sync',
            'sl_ai_pro_cron_cleanup',
            'sl_ai_pro_cron_reporting',
            'sl_ai_pro_cron_license_check'
        ];
        
        foreach ($crons as $cron) {
            $timestamp = wp_next_scheduled($cron);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $cron);
            }
        }
    }
    
    private static function clear_cache(): void {
        $cache = CacheManager::get_instance();
        $cache->flush();
        
        // Transient'leri temizle
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_sl_ai_pro_') . '%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_timeout_sl_ai_pro_') . '%'
            )
        );
    }
    
    private static function send_deactivation_ping(): void {
        $data = [
            'domain' => site_url(),
            'version' => SL_AI_PRO_VERSION,
            'action' => 'deactivate'
        ];
        
        wp_remote_post('https://sosyallift.com/api/v1/plugin/ping', [
            'body' => $data,
            'timeout' => 5,
            'blocking' => false
        ]);
    }
}