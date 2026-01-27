<?php
// Plugin default değerleri
return [
    // Genel ayarlar
    'general' => [
        'timezone' => wp_timezone_string(),
        'currency' => 'TRY',
        'language' => get_locale(),
        'auto_update' => true,
    ],
    
    // SEO ayarları
    'seo' => [
        'auto_scan' => true,
        'scan_interval' => 86400, // 24 saat
        'min_score_threshold' => 70,
        'check_pages' => true,
        'check_posts' => true,
        'check_custom_post_types' => false,
    ],
    
    // Google Ads ayarları
    'google_ads' => [
        'auto_sync' => true,
        'sync_interval' => 3600, // 1 saat
        'budget_alert_threshold' => 80, // %
        'performance_alert_threshold' => -20, // %
    ],
    
    // Intent Detection ayarları
    'intent' => [
        'auto_detect' => true,
        'confidence_threshold' => 0.7,
        'track_commercial' => true,
        'track_informational' => true,
        'track_navigational' => true,
    ],
    
    // Alert ayarları
    'alerts' => [
        'enabled' => true,
        'email_notifications' => true,
        'email_address' => get_option('admin_email'),
        'dashboard_notifications' => true,
        'critical_alerts_only' => false,
    ],
    
    // Report ayarları
    'reports' => [
        'daily_report' => true,
        'weekly_report' => true,
        'monthly_report' => true,
        'report_email' => get_option('admin_email'),
        'include_charts' => true,
        'include_recommendations' => true,
    ],
    
    // Performance ayarları
    'performance' => [
        'data_retention_days' => 90,
        'batch_size' => 100,
        'memory_limit' => '256M',
        'execution_time' => 30,
    ],
];