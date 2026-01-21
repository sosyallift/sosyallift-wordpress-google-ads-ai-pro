<?php
namespace SosyalliftAIPro\Database\Migrations;

class Migration_0001_Create_Initial_Tables {
    
    public function up(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Ana tabloları oluştur
        $tables = [
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
        ];
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
                dbDelta($sql);
            }
        }
    }
    
    public function down(): void {
        global $wpdb;
        
        $tables = [
            "{$wpdb->prefix}sl_ai_conversions",
            "{$wpdb->prefix}sl_ai_intent",
            "{$wpdb->prefix}sl_ai_scores",
            "{$wpdb->prefix}sl_ai_logs",
            "{$wpdb->prefix}sl_ai_keywords",
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}