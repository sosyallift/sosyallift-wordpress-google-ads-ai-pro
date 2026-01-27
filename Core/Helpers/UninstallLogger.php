<?php
namespace SosyalliftAIPro\Core\Helpers;

defined('ABSPATH') || exit;

class UninstallLogger {

    private const LOG_FILE = WP_CONTENT_DIR . '/sl-ai-uninstall.log';

    public static function record(string $message): void {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

        if (file_exists(self::LOG_FILE) && filesize(self::LOG_FILE) > SL_AI_PRO_MAX_LOG_SIZE) {
            @unlink(self::LOG_FILE);
        }

        @file_put_contents(self::LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    }
}
