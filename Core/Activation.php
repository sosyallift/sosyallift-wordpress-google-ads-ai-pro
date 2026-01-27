<?php
namespace SosyalliftAIPro\Core;

defined('ABSPATH') || exit;

class Activation {

    public static function activate() {

        // Agent token (one-time)
        if (!get_option('sosyallift_agent_token')) {
            update_option(
                'sosyallift_agent_token',
                wp_generate_password(32, false)
            );
        }

        // ileride: db tables, defaults
    }
}
