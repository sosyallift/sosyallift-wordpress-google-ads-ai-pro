<?php
/**
 * Sosyallift AI Pro - Enterprise SEO & Ads Intelligence Platform
 * 
 * @package     Sosyallift_AI_Pro
 * @author      Sosyallift
 * @copyright   2024 Sosyallift
 * @license     GPL-3.0+
 * @version     2.0.0
 * 
 * @wordpress-plugin
 * Plugin Name: Sosyallift AI Pro
 * Plugin URI:  https://sosyallift.com/ai-pro
 * Description: Enterprise-grade SEO & Ads Intelligence with AI-powered analytics, intent detection, and automated optimization
 * Version:     2.0.0
 * Author:      Sosyallift
 * Author URI:  https://sosyallift.com
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: sosyallift-ai-pro
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires WP:  5.8
 * WC requires at least: 6.0
 * Network:      true
 */

// Strict mode
declare(strict_types=1);

// Prevent direct access
defined('ABSPATH') || exit('Direct access not allowed.');

// Composer autoload
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Define constants
define('SL_AI_PRO_VERSION', '2.0.0');
define('SL_AI_PRO_FILE', __FILE__);
define('SL_AI_PRO_PATH', plugin_dir_path(__FILE__));
define('SL_AI_PRO_URL', plugin_dir_url(__FILE__));
define('SL_AI_PRO_BASENAME', plugin_basename(__FILE__));
define('SL_AI_PRO_MIN_PHP', '7.4');
define('SL_AI_PRO_MIN_WP', '5.8');
define('SL_AI_PRO_DB_VERSION', '2.0');
define('SL_AI_PRO_CACHE_GROUP', 'sl_ai_cache');
define('SL_AI_PRO_API_TIMEOUT', 30);
define('SL_AI_PRO_MAX_LOG_SIZE', 104857600); // 100MB

// Check requirements
if (!SosyalliftAIPro\Core\Requirements::check()) {
    add_action('admin_notices', [SosyalliftAIPro\Core\Requirements::class, 'show_notice']);
    return;
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, [SosyalliftAIPro\Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [SosyalliftAIPro\Core\Deactivator::class, 'deactivate']);
register_uninstall_hook(__FILE__, [SosyalliftAIPro\Core\Uninstaller::class, 'uninstall']);

// Initialize the plugin
add_action('plugins_loaded', function () {
    // Load text domain
    load_plugin_textdomain(
        'sosyallift-ai-pro',
        false,
        dirname(SL_AI_PRO_BASENAME) . '/languages'
    );

    // Bootstrap the application
    SosyalliftAIPro\Core\Bootstrap::init();
}, 5);
