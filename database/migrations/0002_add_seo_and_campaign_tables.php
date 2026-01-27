<?php
namespace SosyalliftAIPro\Database\Migrations;

class Migration_0002_Add_SEO_And_Campaign_Tables {
    
    public function up(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
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
            "{$wpdb->prefix}sl_ai_campaigns",
            "{$wpdb->prefix}sl_ai_alerts",
            "{$wpdb->prefix}sl_ai_pages",
            "{$wpdb->prefix}sl_ai_seo_data",
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}