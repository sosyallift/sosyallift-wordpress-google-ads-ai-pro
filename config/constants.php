<?php
// Plugin konfigürasyon sabitleri
return [
    // API ayarları
    'api' => [
        'base_url' => 'https://api.sosyallift.com/v1/',
        'timeout' => 30,
        'retry_attempts' => 3,
    ],
    
    // Cache ayarları
    'cache' => [
        'enabled' => true,
        'default_ttl' => 300,
        'prefix' => 'sl_ai_pro_',
    ],
    
    // Log ayarları
    'log' => [
        'enabled' => true,
        'level' => defined('WP_DEBUG') && WP_DEBUG ? 'debug' : 'error',
        'max_file_size' => 10485760, // 10MB
        'retention_days' => 30,
    ],
    
    // Cron ayarları
    'cron' => [
        'sync_interval' => 300, // 5 dakika
        'cleanup_interval' => 86400, // 24 saat
        'reporting_interval' => 86400, // 24 saat
    ],
    
    // UI ayarları
    'ui' => [
        'admin_menu_position' => 30,
        'admin_menu_icon' => 'dashicons-chart-area',
        'dashboard_widgets' => true,
    ],
    
    // Feature flags
    'features' => [
        'google_ads' => true,
        'seo_analysis' => true,
        'intent_detection' => true,
        'competitor_analysis' => true,
        'predictive_analytics' => true,
        'automated_optimization' => false, // Beta
    ],
];
