<?php
namespace SosyalliftAIPro\Database\Migrations;

class Migration_0003_Add_Competitor_Analysis {
    
    public function up(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = [
            "{$wpdb->prefix}sl_ai_competitors" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_competitors (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    domain varchar(255) NOT NULL,
                    name varchar(255) DEFAULT NULL,
                    authority_score int(11) DEFAULT 0,
                    backlinks_count int(11) DEFAULT 0,
                    organic_keywords int(11) DEFAULT 0,
                    organic_traffic int(11) DEFAULT 0,
                    top_keywords text,
                    last_analyzed datetime DEFAULT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY domain (domain),
                    KEY authority_score (authority_score)
                ) $charset_collate;
            ",
            
            "{$wpdb->prefix}sl_ai_competitor_keywords" => "
                CREATE TABLE {$wpdb->prefix}sl_ai_competitor_keywords (
                    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    competitor_id bigint(20) UNSIGNED NOT NULL,
                    keyword varchar(255) NOT NULL,
                    position decimal(5,1) DEFAULT NULL,
                    traffic_share decimal(5,2) DEFAULT 0.00,
                    difficulty int(11) DEFAULT 0,
                    cpc decimal(10,2) DEFAULT 0.00,
                    volume int(11) DEFAULT 0,
                    analyzed_date date NOT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY competitor_id (competitor_id),
                    KEY keyword (keyword),
                    KEY analyzed_date (analyzed_date),
                    FOREIGN KEY (competitor_id) REFERENCES {$wpdb->prefix}sl_ai_competitors(id) ON DELETE CASCADE
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
            "{$wpdb->prefix}sl_ai_competitor_keywords",
            "{$wpdb->prefix}sl_ai_competitors",
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
}