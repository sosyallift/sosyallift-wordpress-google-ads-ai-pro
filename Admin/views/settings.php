<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('sosyallift_ai_pro_settings', array());
$nonce = wp_create_nonce('sosyallift_settings_save');
?>

<div class="wrap sosyallift-settings">
    <h1 class="wp-heading-inline">
        <?php _e('SosyalLift AI Pro Settings', 'sosyallift-ai-pro'); ?>
    </h1>
    
    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Settings saved successfully!', 'sosyallift-ai-pro'); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="sosyallift_save_settings">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_key"><?php _e('API Key', 'sosyallift-ai-pro'); ?></label>
                </th>
                <td>
                    <input type="password" 
                           id="api_key" 
                           name="sosyallift_settings[api_key]" 
                           value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('Enter your SosyalLift AI API key', 'sosyallift-ai-pro'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_optimize"><?php _e('Auto Optimize', 'sosyallift-ai-pro'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="auto_optimize" 
                           name="sosyallift_settings[auto_optimize]" 
                           value="yes" 
                           <?php checked($settings['auto_optimize'] ?? 'yes', 'yes'); ?>>
                    <label for="auto_optimize">
                        <?php _e('Automatically optimize campaigns', 'sosyallift-ai-pro'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="daily_budget"><?php _e('Daily Budget ($)', 'sosyallift-ai-pro'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="daily_budget" 
                           name="sosyallift_settings[daily_budget]" 
                           value="<?php echo esc_attr($settings['daily_budget'] ?? 100); ?>" 
                           min="1" 
                           max="10000" 
                           step="1" 
                           class="small-text">
                    <p class="description">
                        <?php _e('Default daily budget for new campaigns', 'sosyallift-ai-pro'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="notification_email"><?php _e('Notification Email', 'sosyallift-ai-pro'); ?></label>
                </th>
                <td>
                    <input type="email" 
                           id="notification_email" 
                           name="sosyallift_settings[notification_email]" 
                           value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('Email for optimization notifications', 'sosyallift-ai-pro'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'sosyallift-ai-pro')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Settings form validation
    $('form').on('submit', function(e) {
        var apiKey = $('#api_key').val();
        if (!apiKey || apiKey.trim() === '') {
            alert('<?php _e('API Key is required!', 'sosyallift-ai-pro'); ?>');
            e.preventDefault();
            return false;
        }
    });
});
</script>