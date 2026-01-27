<?php
namespace SosyalliftAIPro\Core\Helpers;

defined('ABSPATH') || exit;

class UninstallHelper {

    /**
     * Kullanıcı ayarına göre tüm veriler silinsin mi?
     */
    public static function should_remove_data(): bool {
        return (bool) get_option('sl_ai_remove_data_on_uninstall', false);
    }

    /**
     * DRY RUN aktif mi?
     * true → hiçbir şey silinmez, sadece loglanır
     */
    public static function is_dry_run(): bool {
        return defined('SL_AI_UNINSTALL_DRY_RUN') && SL_AI_UNINSTALL_DRY_RUN === true;
    }

    /**
     * Log helper (UninstallLogger wrapper)
     */
    public static function log(string $message): void {
        if (class_exists(UninstallLogger::class)) {
            UninstallLogger::record($message);
        }
    }
}
