<?php
namespace SosyalliftAIPro\Core\Helpers;

defined('ABSPATH') || exit;

class UninstallHelper {

    /**
     * Kullanıcı verileri silinsin mi?
     * Ayar kapalıysa uninstall sadece cron + cache temizler
     */
    public static function should_remove_data(): bool {

        /**
         * Opsiyon önceliği:
         * 1️⃣ sabit (CI / CLI / test)
         * 2️⃣ option (admin toggle)
         * 3️⃣ default = true
         */

        if (defined('SL_AI_PRO_FORCE_REMOVE_DATA')) {
            return (bool) SL_AI_PRO_FORCE_REMOVE_DATA;
        }

        $value = get_option('sl_ai_pro_remove_data_on_uninstall', true);

        return (bool) apply_filters(
            'sl_ai_pro_should_remove_data',
            $value
        );
    }

    /**
     * DRY RUN modu açık mı?
     * Hiçbir delete işlemi yapılmaz, sadece log atılır
     */
    public static function is_dry_run(): bool {

        if (defined('SL_AI_PRO_UNINSTALL_DRY_RUN')) {
            return (bool) SL_AI_PRO_UNINSTALL_DRY_RUN;
        }

        return (bool) apply_filters(
            'sl_ai_pro_uninstall_dry_run',
            false
        );
    }

    /**
     * Güvenli uninstall log writer
     * Logger yoksa error_log fallback
     */
    public static function log(string $message): void {

        $message = '[Sosyallift AI Pro][UNINSTALL] ' . $message;

        if (class_exists(UninstallLogger::class)) {
            UninstallLogger::record($message);
            return;
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }

    /**
     * Helper: delete işlemi çalıştırılabilir mi?
     * Tek satırlık guard olarak kullanılır
     */
    public static function can_delete(string $context): bool {

        if (self::is_dry_run()) {
            self::log('DRY RUN: ' . $context);
            return false;
        }

        return true;
    }
}
