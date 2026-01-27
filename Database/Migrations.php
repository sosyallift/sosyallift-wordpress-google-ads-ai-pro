<?php
namespace SosyalliftAIPro\Core\Database;

use SosyalliftAIPro\Core\Helpers\UninstallHelper;
use SosyalliftAIPro\Core\Helpers\UninstallLogger;

defined('ABSPATH') || exit;

class Migrations {

    public static function rollback_all(): void {

        global $wpdb;

        if (UninstallHelper::is_dry_run()) {
            UninstallLogger::record('DRY RUN: migrations rollback');
            return;
        }

        $table = $wpdb->prefix . 'sl_ai_migrations';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return;
        }

        $migrations = $wpdb->get_results("SELECT migration FROM {$table} ORDER BY id DESC");

        foreach ($migrations as $migration) {
            $class = self::resolve_migration_class($migration->migration);

            if ($class && method_exists($class, 'down')) {
                call_user_func([$class, 'down']);
                UninstallLogger::record('Migration rollback: ' . $migration->migration);
            }
        }

        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    private static function resolve_migration_class(string $migration): ?string {
        $class = 'SosyalliftAIPro\\Database\\Migrations\\' . $migration;
        return class_exists($class) ? $class : null;
    }
}
