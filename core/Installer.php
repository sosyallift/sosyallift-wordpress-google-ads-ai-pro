<?php
namespace SosyalliftAIPro\Core;

class Installer {
    private static $db_version = '2.0.0';
    
    public static function install(): void {
        self::create_tables();
        self::update_db_version();
    }
    
    private static function create_tables(): void {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = self::get_table_schemas($charset_collate);
        
        foreach ($tables as $table_name => $sql) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
                dbDelta($sql);
            }
        }
    }
    
    private static function get_table_schemas($charset_collate): array {
        global $wpdb;
        
        return [
            "{$wpdb->prefix}sl_ai_keywords" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_keywords (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    keyword varchar(255) NOT NULL,
                    intent_type varchar(50) DEFAULT NULL,
                    intent_score decimal(5,2) DEFAULT 0.00,
                    commercial_score decimal(5,2) DEFAULT 0.00,
                    monthly_searches int(11) DEFAULT 0,
                    competition varchar(50) DEFAULT 'low',
                    cpc decimal(10,2) DEFAULT 0.00,
                    source varchar(100) DEFAULT NULL,
                    campaign_id bigint(20) DEFAULT NULL,
                    status varchar(50) DEFAULT 'active',
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY keyword (keyword),
                    KEY intent_type (intent_type),
                    KEY status (status),
                    KEY campaign_id (campaign_id)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_logs" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_logs (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    log_level varchar(20) NOT NULL,
                    message text NOT NULL,
                    context longtext,
                    source varchar(100) DEFAULT NULL,
                    user_id bigint(20) DEFAULT NULL,
                    ip_address varchar(45) DEFAULT NULL,
                    user_agent text,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY log_level (log_level),
                    KEY source (source),
                    KEY user_id (user_id),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_scores" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_scores (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    page_id bigint(20) DEFAULT NULL,
                    url varchar(500) DEFAULT NULL,
                    seo_score decimal(5,2) DEFAULT 0.00,
                    performance_score decimal(5,2) DEFAULT 0.00,
                    accessibility_score decimal(5,2) DEFAULT 0.00,
                    best_practices_score decimal(5,2) DEFAULT 0.00,
                    total_score decimal(5,2) DEFAULT 0.00,
                    issues_count int(11) DEFAULT 0,
                    data longtext,
                    analyzed_at datetime DEFAULT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY page_id (page_id),
                    KEY url (url(191)),
                    KEY total_score (total_score),
                    KEY analyzed_at (analyzed_at)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_intent" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_intent (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    query text NOT NULL,
                    intent_type varchar(50) NOT NULL,
                    confidence decimal(5,2) DEFAULT 0.00,
                    source varchar(100) DEFAULT NULL,
                    metadata longtext,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY intent_type (intent_type),
                    KEY confidence (confidence),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_conversions" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_conversions (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    conversion_type varchar(50) NOT NULL,
                    value decimal(10,2) DEFAULT 0.00,
                    source varchar(100) DEFAULT NULL,
                    medium varchar(100) DEFAULT NULL,
                    campaign varchar(255) DEFAULT NULL,
                    keyword varchar(255) DEFAULT NULL,
                    page_url varchar(500) DEFAULT NULL,
                    user_id bigint(20) DEFAULT NULL,
                    session_id varchar(100) DEFAULT NULL,
                    metadata longtext,
                    converted_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY conversion_type (conversion_type),
                    KEY source (source),
                    KEY medium (medium),
                    KEY keyword (keyword),
                    KEY converted_at (converted_at)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_seo_data" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_seo_data (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    keyword_id bigint(20) UNSIGNED NOT NULL,
                    page_id bigint(20) DEFAULT NULL,
                    position decimal(5,1) DEFAULT NULL,
                    impressions int(11) DEFAULT 0,
                    clicks int(11) DEFAULT 0,
                    ctr decimal(5,2) DEFAULT 0.00,
                    date date NOT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY keyword_id (keyword_id),
                    KEY page_id (page_id),
                    KEY date (date),
                    FOREIGN KEY (keyword_id) REFERENCES {$wpdb->prefix}sl_ai_keywords(id) ON DELETE CASCADE
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_pages" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_pages (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    url varchar(500) NOT NULL,
                    title varchar(255) DEFAULT NULL,
                    meta_description text,
                    word_count int(11) DEFAULT 0,
                    seo_score decimal(5,2) DEFAULT 0.00,
                    status_code int(11) DEFAULT NULL,
                    load_time decimal(8,3) DEFAULT NULL,
                    last_crawled datetime DEFAULT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY url (url(191)),
                    KEY seo_score (seo_score),
                    KEY last_crawled (last_crawled)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_alerts" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_alerts (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    alert_type varchar(50) NOT NULL,
                    severity varchar(20) NOT NULL DEFAULT 'warning',
                    title varchar(255) NOT NULL,
                    message text,
                    data longtext,
                    status varchar(20) NOT NULL DEFAULT 'unread',
                    read_at datetime DEFAULT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY alert_type (alert_type),
                    KEY severity (severity),
                    KEY status (status),
                    KEY created_at (created_at)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_campaigns" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_campaigns (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    campaign_type varchar(50) NOT NULL,
                    status varchar(50) NOT NULL DEFAULT 'active',
                    budget decimal(10,2) DEFAULT 0.00,
                    start_date date DEFAULT NULL,
                    end_date date DEFAULT NULL,
                    target_url varchar(500) DEFAULT NULL,
                    settings longtext,
                    performance_data longtext,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY campaign_type (campaign_type),
                    KEY status (status),
                    KEY start_date (start_date),
                    KEY end_date (end_date)
                ) $charset_collate;
            ",
        ];
    }
    
    private static function update_db_version(): void {
        update_option('sl_ai_pro_db_version', self::$db_version);
    }
    
    public static function needs_upgrade(): bool {
        $current_version = get_option('sl_ai_pro_db_version', '1.0.0');
        return version_compare($current_version, self::$db_version, '<');
    }
}