<?php
namespace SosyalliftAIPro\Core\Helpers;

class UninstallHelper {

    public static function is_dry_run(): bool {
        return defined('SL_AI_PRO_UNINSTALL_DRY_RUN') && SL_AI_PRO_UNINSTALL_DRY_RUN === true;
    }

    public static function should_remove_data(): bool {
        return (bool) get_option('sl_ai_pro_remove_data_on_uninstall', false);
    }

    public static function can_delete(string $action): bool {
		if (!UninstallHelper::can_delete('delete_tables')) {
			return;
		}

        if (self::is_dry_run()) {
            UninstallLogger::record("DRY RUN: {$action}");
            return false;
        }

        if (!self::should_remove_data()) {
            UninstallLogger::record("SKIPPED (setting disabled): {$action}");
            return false;
        }

        UninstallLogger::record("EXECUTE: {$action}");
        return true;
    }

    public static function log(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SL-AI-UNINSTALL] ' . $message);
        }
    }
}
