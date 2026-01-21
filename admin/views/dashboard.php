<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap sosyallift-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('SosyalLift AI Pro Dashboard', 'sosyallift-ai-pro'); ?>
    </h1>
    
    <div class="notice notice-info">
        <p><?php _e('Welcome to SosyalLift AI Pro! This plugin helps optimize your Google Ads campaigns using AI technology.', 'sosyallift-ai-pro'); ?></p>
    </div>
    
    <div class="dashboard-widgets">
        <div class="card">
            <h3><?php _e('Campaign Overview', 'sosyallift-ai-pro'); ?></h3>
            <p><?php _e('No campaigns yet. Create your first campaign to get started.', 'sosyallift-ai-pro'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=sosyallift-ai-pro-settings'); ?>" class="button button-primary">
                <?php _e('Go to Settings', 'sosyallift-ai-pro'); ?>
            </a>
        </div>
        
        <div class="card">
            <h3><?php _e('Quick Actions', 'sosyallift-ai-pro'); ?></h3>
            <ul>
                <li><a href="#"><?php _e('Analyze Keywords', 'sosyallift-ai-pro'); ?></a></li>
                <li><a href="#"><?php _e('View Reports', 'sosyallift-ai-pro'); ?></a></li>
                <li><a href="#"><?php _e('Optimize Campaigns', 'sosyallift-ai-pro'); ?></a></li>
            </ul>
        </div>
    </div>
    
    <style>
    .sosyallift-dashboard .dashboard-widgets {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .sosyallift-dashboard .card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .sosyallift-dashboard .card h3 {
        margin-top: 0;
        color: #23282d;
    }
    </style>
</div>