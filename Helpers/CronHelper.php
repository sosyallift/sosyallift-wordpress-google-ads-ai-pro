<?php
namespace SosyalliftAIPro\Core\Helpers;

defined('ABSPATH') || exit;

class CronHelper {

    public static function register_intervals(array $schedules): array {
        $schedules['sl_ai_pro_5min'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes', 'sosyallift-ai-pro')
        ];

        return $schedules;
    }
}
