<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
global $wpdb;
// Tabloları sil
$tables = [
    $wpdb->prefix . 'sl_ai_logs',
    $wpdb->prefix . 'sl_ai_keywords',
    $wpdb->prefix . 'sl_ai_scores'
];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}
// Options'ları sil
delete_option('sl_ai_installed');
delete_option('sl_ai_settings');
delete_option('sl_ai_license');
