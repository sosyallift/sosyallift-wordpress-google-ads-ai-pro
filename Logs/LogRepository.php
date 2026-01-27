<?php
namespace SosyalliftAIPro\Core\Logs;

defined('ABSPATH') || exit;

class LogRepository {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'sosyallift_logs';
    }

    public function insert(
        string $type,
        string $source,
        string $message,
        array $context = [],
        string $level = 'info'
    ): void {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'log_type'   => $type,
                'source'     => $source,
                'message'    => $message,
                'context'    => maybe_serialize($context),
                'level'      => $level,
                'created_at' => current_time('mysql')
            ],
            ['%s','%s','%s','%s','%s','%s']
        );
    }

    public function fetch(array $args = []): array {
        global $wpdb;

        $where = '1=1';

        if (!empty($args['type'])) {
            $where .= $wpdb->prepare(' AND log_type = %s', $args['type']);
        }

        if (!empty($args['level'])) {
            $where .= $wpdb->prepare(' AND level = %s', $args['level']);
        }

        return $wpdb->get_results(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at DESC LIMIT 500",
            ARRAY_A
        );
    }
}
