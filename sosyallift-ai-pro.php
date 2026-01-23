<?php
/**
 * Sosyallift AI Pro - Enterprise SEO & Ads Intelligence Platform
 * 
 * @package     Sosyallift_AI_Pro
 * @author      Sosyallift
 * @copyright   2026 Sosyallift
 * @license     GPL-3.0+
 * @version     2.0.0
 * 
 * @wordpress-plugin
 * Plugin Name: Sosyallift AI Pro
 * Plugin URI:  https://sosyallift.com/ai-pro
 * Description: Kurumsal düzeyde SERP SEO ve Google Ads ajanı olarak çalışan bu eklenti; siteyi yapay zekâ uyumlu hale getirir, kullanıcı davranışlarını analiz ederek örüntüler oluşturur ve reklamlardan gelen anormal trafiği tespit eder.
 * Version:     2.0.0
 * Author:      Sosyallift - Serkan Bekiroğulları
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
// Activation hook'a bu kısmı ekleyinregister_activation_hook(__FILE__, 'sl_ai_pro_activate');function sl_ai_pro_activate() {    // ... mevcut activation kodunuz ...        // Default settings ekleyin    add_option('sl_ai_remove_data_on_uninstall', 0); // Default: don't remove data    add_option('sl_ai_enable_debug', 0);    add_option('sl_ai_cron_interval', 'daily');}// Uninstall hook'unu güncelleyinregister_uninstall_hook(__FILE__, 'sl_ai_pro_uninstall');function sl_ai_pro_uninstall() {    // Check if user has permission    if (!current_user_can('activate_plugins')) {        return;    }        // Check if we should remove data    $remove_data = get_option('sl_ai_remove_data_on_uninstall', false);        if ($remove_data) {        // Include the uninstall script        require_once plugin_dir_path(__FILE__) . 'uninstall.php';    }}
// 1. ÖNCE SABİTLERİ TANIMLA
define('SL_AI_PRO_VERSION', '2.0.0');
define('SL_AI_PRO_FILE', __FILE__);
define('SL_AI_PRO_PATH', plugin_dir_path(__FILE__));
define('SL_AI_PRO_URL', plugin_dir_url(__FILE__));
define('SL_AI_PRO_BASENAME', plugin_basename(__FILE__));
define('SL_AI_PRO_MIN_PHP', '7.4');  // BURASI ÖNEMLİ!
define('SL_AI_PRO_MIN_WP', '5.8');   // BURASI ÖNEMLİ!
define('SL_AI_PRO_DB_VERSION', '2.0');
define('SL_AI_PRO_CACHE_GROUP', 'sl_ai_cache');
define('SL_AI_PRO_API_TIMEOUT', 30);
define('SL_AI_PRO_MAX_LOG_SIZE', 104857600); // 100MB

// 2. MANUEL AUTOLOADER - Requirements.php'yi yüklemek için
spl_autoload_register(function ($class) {
    $prefix = 'SosyalliftAIPro\\';
    $base_dir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 3. Composer autoload (varsa)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 4. Hata ayıklama - Requirements sınıfı var mı kontrol et
error_log("SosyalliftAIPro: Plugin yükleniyor...");
error_log("Requirements sınıfı var mı: " . (class_exists('SosyalliftAIPro\Core\Requirements') ? 'EVET' : 'HAYIR'));

// 5. Check requirements - SABİTLER ARTIK TANIMLI
if (!SosyalliftAIPro\Core\Requirements::check()) {
    add_action('admin_notices', [SosyalliftAIPro\Core\Requirements::class, 'show_notice']);
    return;
}

// 6. Register activation/deactivation hooks
register_activation_hook(__FILE__, [SosyalliftAIPro\Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [SosyalliftAIPro\Core\Deactivator::class, 'deactivate']);
register_uninstall_hook(__FILE__, [SosyalliftAIPro\Core\Uninstaller::class, 'uninstall']);

// 7. Initialize the plugin
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