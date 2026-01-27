<?php
defined('ABSPATH') || exit;

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'sosyallift-ai-pro'));
}

// Save settings
if (isset($_POST['sl_ai_pro_settings_nonce']) && wp_verify_nonce($_POST['sl_ai_pro_settings_nonce'], 'sl_ai_pro_save_settings')) {

    update_option('sl_ai_pro_google_api_key', sanitize_text_field($_POST['sl_ai_pro_google_api_key'] ?? ''));
    update_option('sl_ai_pro_sosyallift_api_key', sanitize_text_field($_POST['sl_ai_pro_sosyallift_api_key'] ?? ''));

    update_option('sl_ai_pro_uninstall_remove_data', isset($_POST['sl_ai_pro_uninstall_remove_data']) ? 1 : 0);
    update_option('sl_ai_pro_dry_run', isset($_POST['sl_ai_pro_dry_run']) ? 1 : 0);
    update_option('sl_ai_pro_debug', isset($_POST['sl_ai_pro_debug']) ? 1 : 0);

    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'sosyallift-ai-pro') . '</p></div>';
}

$google_api_key = get_option('sl_ai_pro_google_api_key', '');
$sosyallift_api_key = get_option('sl_ai_pro_sosyallift_api_key', '');
$uninstall_remove_data = get_option('sl_ai_pro_uninstall_remove_data', 0);
$dry_run = get_option('sl_ai_pro_dry_run', 0);
$debug = get_option('sl_ai_pro_debug', 0);
?>

<div class="wrap" id="sl-ai-pro-settings">
    <h1 class="sl-ai-pro-title"><?php esc_html_e('Settings', 'sosyallift-ai-pro'); ?></h1>

    <form method="post">
        <?php wp_nonce_field('sl_ai_pro_save_settings', 'sl_ai_pro_settings_nonce'); ?>

        <h2><?php esc_html_e('API Keys', 'sosyallift-ai-pro'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="sl_ai_pro_google_api_key"><?php esc_html_e('Google API Key', 'sosyallift-ai-pro'); ?></label></th>
                <td><input class="regular-text" type="text" id="sl_ai_pro_google_api_key" name="sl_ai_pro_google_api_key" value="<?php echo esc_attr($google_api_key); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="sl_ai_pro_sosyallift_api_key"><?php esc_html_e('Sosyallift API Key', 'sosyallift-ai-pro'); ?></label></th>
                <td><input class="regular-text" type="text" id="sl_ai_pro_sosyallift_api_key" name="sl_ai_pro_sosyallift_api_key" value="<?php echo esc_attr($sosyallift_api_key); ?>"></td>
            </tr>
        </table>

        <h2><?php esc_html_e('Uninstall / Safety', 'sosyallift-ai-pro'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Remove data on uninstall', 'sosyallift-ai-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="sl_ai_pro_uninstall_remove_data" value="1" <?php checked($uninstall_remove_data, 1); ?>>
                        <?php esc_html_e('If enabled, all plugin data will be removed on uninstall.', 'sosyallift-ai-pro'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Dry run mode', 'sosyallift-ai-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="sl_ai_pro_dry_run" value="1" <?php checked($dry_run, 1); ?>>
                        <?php esc_html_e('If enabled, uninstall will simulate deletion without removing data.', 'sosyallift-ai-pro'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Debug mode', 'sosyallift-ai-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="sl_ai_pro_debug" value="1" <?php checked($debug, 1); ?>>
                        <?php esc_html_e('If enabled, debug logs will be recorded.', 'sosyallift-ai-pro'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'sosyallift-ai-pro')); ?>
    </form>
</div>
