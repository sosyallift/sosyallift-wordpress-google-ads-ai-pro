<?php
namespace SosyalliftAIPro\Core;

class MigrationManager {
    private $migrations_dir;
    
    public function __construct() {
        $this->migrations_dir = SL_AI_PRO_PATH . 'database/migrations/';
    }
    
    public function run(): void {
        $this->ensure_migrations_table();
        
        $migrations = $this->get_pending_migrations();
        
        foreach ($migrations as $migration) {
            $this->run_migration($migration);
        }
    }
    
    private function ensure_migrations_table(): void {
        global $wpdb;
        
        $table_name = "{$wpdb->prefix}sl_ai_migrations";
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY migration (migration)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function get_pending_migrations(): array {
        $all_migrations = $this->get_all_migrations();
        $executed_migrations = $this->get_executed_migrations();
        
        return array_diff($all_migrations, $executed_migrations);
    }
    
    private function get_all_migrations(): array {
        $migrations = [];
        
        if (!file_exists($this->migrations_dir)) {
            return $migrations;
        }
        
        $files = glob($this->migrations_dir . '*.php');
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.php');
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    private function get_executed_migrations(): array {
        global $wpdb;
        
        $table_name = "{$wpdb->prefix}sl_ai_migrations";
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return [];
        }
        
        $results = $wpdb->get_col("SELECT migration FROM {$table_name} ORDER BY migration");
        
        return $results;
    }
    
    private function run_migration(string $migration): void {
        global $wpdb;
        
        $file = $this->migrations_dir . $migration . '.php';
        
        if (!file_exists($file)) {
            Logger::error("Migration file not found: {$migration}");
            return;
        }
        
        include $file;
        
        if (!class_exists($migration)) {
            Logger::error("Migration class not found: {$migration}");
            return;
        }
        
        $instance = new $migration();
        
        try {
            // Run migration
            $instance->up();
            
            // Record migration
            $batch = $this->get_next_batch_number();
            
            $wpdb->insert(
                "{$wpdb->prefix}sl_ai_migrations",
                [
                    'migration' => $migration,
                    'batch' => $batch,
                ]
            );
            
            Logger::info("Migration executed: {$migration}");
            
        } catch (\Exception $e) {
            Logger::error("Migration failed: {$migration}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    private function get_next_batch_number(): int {
        global $wpdb;
        
        $table_name = "{$wpdb->prefix}sl_ai_migrations";
        
        $max_batch = $wpdb->get_var("SELECT MAX(batch) FROM {$table_name}");
        
        return (int) $max_batch + 1;
    }
    
    public function rollback($steps = 1): void {
        $migrations = $this->get_last_batch_migrations($steps);
        
        foreach ($migrations as $migration) {
            $this->rollback_migration($migration);
        }
    }
    
    private function get_last_batch_migrations($steps = 1): array {
        global $wpdb;
        
        $table_name = "{$wpdb->prefix}sl_ai_migrations";
        
        $batch = $wpdb->get_var("SELECT MAX(batch) FROM {$table_name}");
        
        if (!$batch) {
            return [];
        }
        
        $start_batch = max(1, $batch - $steps + 1);
        
        $migrations = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT migration FROM {$table_name} WHERE batch >= %d ORDER BY id DESC",
                $start_batch
            )
        );
        
        return $migrations;
    }
    
    private function rollback_migration(string $migration): void {
        global $wpdb;
        
        $file = $this->migrations_dir . $migration . '.php';
        
        if (!file_exists($file)) {
            Logger::error("Migration file not found for rollback: {$migration}");
            return;
        }
        
        include $file;
        
        if (!class_exists($migration)) {
            Logger::error("Migration class not found for rollback: {$migration}");
            return;
        }
        
        $instance = new $migration();
        
        try {
            // Rollback migration
            $instance->down();
            
            // Remove migration record
            $wpdb->delete(
                "{$wpdb->prefix}sl_ai_migrations",
                ['migration' => $migration]
            );
            
            Logger::info("Migration rolled back: {$migration}");
            
        } catch (\Exception $e) {
            Logger::error("Migration rollback failed: {$migration}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function get_status(): array {
        $all_migrations = $this->get_all_migrations();
        $executed_migrations = $this->get_executed_migrations();
        
        $status = [];
        
        foreach ($all_migrations as $migration) {
            $status[$migration] = [
                'migration' => $migration,
                'executed' => in_array($migration, $executed_migrations),
                'executed_at' => null,
                'batch' => null,
            ];
            
            if ($status[$migration]['executed']) {
                $details = $this->get_migration_details($migration);
                $status[$migration]['executed_at'] = $details->executed_at ?? null;
                $status[$migration]['batch'] = $details->batch ?? null;
            }
        }
        
        return $status;
    }
    
    private function get_migration_details(string $migration): ?object {
        global $wpdb;
        
        $table_name = "{$wpdb->prefix}sl_ai_migrations";
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE migration = %s",
                $migration
            )
        );
        
        return $result;
    }
}