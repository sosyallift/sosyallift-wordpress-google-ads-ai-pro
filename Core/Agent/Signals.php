<?php
namespace SosyalliftAIPro\Core\Agent;

class Signals {

    public static function collect(): array {
        return [
            'time'        => current_time('mysql'),
            'memory'      => memory_get_usage(),
            'site_health' => get_option('blog_public'),
        ];
    }
}
