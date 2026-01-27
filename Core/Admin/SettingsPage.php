<?php
namespace SosyalliftAIPro\Core\Admin;

defined('ABSPATH') || exit;

class SettingsPage {

    public static function init(): void {
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function register_menu(): void {
        add_menu_page(
            __('Sosyallift AI Pro', 'sosyallift-ai-pro'),
            __('Sosyallift AI Pro', 'sosyallift-ai-pro'),
            'manage_options',
            'sl-ai-pro-settings',
            [self::class, 'render_page'],
            'dashicons-admin-generic',
            60
        );
    }

    public static function register_settings(): void {
        register_setting('sl_ai_pro_settings', 'sl_ai_remove_data_on_uninstall');
        register_setting('sl_ai_pro_settings', 'sl_ai_uninstall_dry_run');
    }

    public static function render_page(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('Sosyallift AI Pro Settings', 'sosyallift-ai-pro'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('sl_ai_pro_settings'); ?>
                <?php do_settings_sections('sl_ai_pro_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Uninstall Data Removal', 'sosyallift-ai-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sl_ai_remove_data_on_uninstall" value="1" <?php checked(1, get_option('sl_ai_remove_data_on_uninstall', 0)); ?> />
                                <?php _e('Remove all data on uninstall', 'sosyallift-ai-pro'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Eğer işaretli değilse, uninstall sadece cron ve cache temizler. (Veri silinmez)', 'sosyallift-ai-pro'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Uninstall Dry Run', 'sosyallift-ai-pro'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sl_ai_uninstall_dry_run" value="1" <?php checked(1, get_option('sl_ai_uninstall_dry_run', 0)); ?> />
                                <?php _e('Dry run mode', 'sosyallift-ai-pro'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Dry run açıkken hiçbir şey silinmez. Sadece log yazılır.', 'sosyallift-ai-pro'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
