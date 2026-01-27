<?php
namespace SosyalliftAIPro\Core\Logs;

defined('ABSPATH') || exit;

class Logger {

    private static ?Logger $instance = null;
    private LogRepository $repo;

    private function __construct() {
        $this->repo = new LogRepository();
    }

    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(
        string $type,
        string $source,
        string $message,
        array $context = [],
        string $level = 'info'
    ): void {
        $this->repo->insert($type, $source, $message, $context, $level);
    }
}
