<?php
/**
 * Sosyallift AI Pro - Settings Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['sl_ai_save_settings'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'sl_ai_settings_nonce')) {
        wp_die('Security check failed');
    }
    
    // Save general settings
    if (isset($_POST['sl_ai_currency'])) {
        update_option('sl_ai_currency', sanitize_text_field($_POST['sl_ai_currency']));
    }
    
    update_option('sl_ai_auto_sync', isset($_POST['sl_ai_auto_sync']) ? 1 : 0);
    
    if (isset($_POST['sl_ai_cache_ttl'])) {
        update_option('sl_ai_cache_ttl', intval($_POST['sl_ai_cache_ttl']));
    }
    
    // Save Google Ads settings
    if (isset($_POST['sl_ai_google_dev_token'])) {
        update_option('sl_ai_google_dev_token', sanitize_text_field($_POST['sl_ai_google_dev_token']));
    }
    
    if (isset($_POST['sl_ai_google_client_id'])) {
        update_option('sl_ai_google_client_id', sanitize_text_field($_POST['sl_ai_google_client_id']));
    }
    
    if (isset($_POST['sl_ai_google_client_secret'])) {
        update_option('sl_ai_google_client_secret', sanitize_text_field($_POST['sl_ai_google_client_secret']));
    }
    
    if (isset($_POST['sl_ai_google_refresh_token'])) {
        update_option('sl_ai_google_refresh_token', sanitize_text_field($_POST['sl_ai_google_refresh_token']));
    }
    
    // Save API keys
    if (isset($_POST['sl_ai_openai_api_key'])) {
        update_option('sl_ai_openai_api_key', sanitize_text_field($_POST['sl_ai_openai_api_key']));
    }
    
    if (isset($_POST['sl_ai_google_api_key'])) {
        update_option('sl_ai_google_api_key', sanitize_text_field($_POST['sl_ai_google_api_key']));
    }
    
    // Save advanced settings (DATA REMOVAL OPTION)
    update_option('sl_ai_remove_data_on_uninstall', isset($_POST['sl_ai_remove_data_on_uninstall']) ? 1 : 0);
    update_option('sl_ai_enable_debug', isset($_POST['sl_ai_enable_debug']) ? 1 : 0);
    
    if (isset($_POST['sl_ai_cron_interval'])) {
        update_option('sl_ai_cron_interval', sanitize_text_field($_POST['sl_ai_cron_interval']));
    }
    
    // Show success message
    echo '<div class="notice notice-success is-dismissible"><p>Ayarlar kaydedildi!</p></div>';
}

// Handle manual cleanup request
if (isset($_GET['action']) && $_GET['action'] === 'cleanup' && isset($_GET['nonce'])) {
    if (wp_verify_nonce($_GET['nonce'], 'sl_ai_cleanup_data')) {
        if (current_user_can('manage_options')) {
            // Run cleanup
            sl_ai_perform_cleanup();
            
            // Redirect with success message
            wp_redirect(admin_url('admin.php?page=sl-ai-settings&cleanup_completed=1'));
            exit;
        } else {
            wp_die('Yetkiniz yok.');
        }
    } else {
        wp_die('Güvenlik kontrolü başarısız.');
    }
}

// Show cleanup success message
if (isset($_GET['cleanup_completed']) && $_GET['cleanup_completed'] == 1) {
    echo '<div class="notice notice-success is-dismissible"><p>Tüm plugin verileri başarıyla temizlendi!</p></div>';
}

// Function to perform manual cleanup
function sl_ai_perform_cleanup() {
    global $wpdb;
    
    // Tabloları sil
    $tables = [
        $wpdb->prefix . 'sl_ai_logs',
        $wpdb->prefix . 'sl_ai_keywords',
        $wpdb->prefix . 'sl_ai_scores',
        $wpdb->prefix . 'sl_ai_alerts',
        $wpdb->prefix . 'sl_ai_campaigns',
        $wpdb->prefix . 'sl_ai_conversions',
        $wpdb->prefix . 'sl_ai_intent',
        $wpdb->prefix . 'sl_ai_migrations',
        $wpdb->prefix . 'sl_ai_pages',
        $wpdb->prefix . 'sl_ai_seo_data',
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Options'ları sil
    $options = [
        'sl_ai_installed',
        'sl_ai_settings',
        'sl_ai_license',
        'sl_ai_currency',
        'sl_ai_auto_sync',
        'sl_ai_cache_ttl',
        'sl_ai_google_dev_token',
        'sl_ai_google_client_id',
        'sl_ai_google_client_secret',
        'sl_ai_google_refresh_token',
        'sl_ai_openai_api_key',
        'sl_ai_google_api_key',
        'sl_ai_remove_data_on_uninstall',
        'sl_ai_enable_debug',
        'sl_ai_cron_interval',
        'sl_ai_version',
        'sl_ai_db_version',
    ];
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Transient'leri temizle
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_sl_ai_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_sl_ai_%'");
    
    // User meta temizle
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'sl_ai_%'");
    
    // Post meta temizle
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_sl_ai_%'");
    
    // Cron job'ları temizle
    wp_clear_scheduled_hook('sl_ai_daily_sync');
    wp_clear_scheduled_hook('sl_ai_hourly_check');
    
    // Cache temizle
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

// Get current values
$currency = get_option('sl_ai_currency', 'TRY');
$auto_sync = get_option('sl_ai_auto_sync', 1);
$cache_ttl = get_option('sl_ai_cache_ttl', 300);
$google_dev_token = get_option('sl_ai_google_dev_token', '');
$google_client_id = get_option('sl_ai_google_client_id', '');
$google_client_secret = get_option('sl_ai_google_client_secret', '');
$google_refresh_token = get_option('sl_ai_google_refresh_token', '');
$openai_api_key = get_option('sl_ai_openai_api_key', '');
$google_api_key = get_option('sl_ai_google_api_key', '');
$remove_data_on_uninstall = get_option('sl_ai_remove_data_on_uninstall', 0);
$enable_debug = get_option('sl_ai_enable_debug', 0);
$cron_interval = get_option('sl_ai_cron_interval', 'daily');

// Create nonce for cleanup
$cleanup_nonce = wp_create_nonce('sl_ai_cleanup_data');
$cleanup_url = admin_url('admin.php?page=sl-ai-settings&action=cleanup&nonce=' . $cleanup_nonce);
?>

<div class="wrap">
    <h1>AI Intelligence Settings</h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="#general-settings" class="nav-tab nav-tab-active">Genel Ayarlar</a>
        <a href="#api-settings" class="nav-tab">API Ayarları</a>
        <a href="#google-ads-settings" class="nav-tab">Google Ads</a>
        <a href="#advanced-settings" class="nav-tab">Gelişmiş</a>
    </h2>
    
    <form method="post" action="">
        <?php wp_nonce_field('sl_ai_settings_nonce'); ?>
        
        <div id="general-settings" class="tab-content">
            <table class="form-table">
                <tr>
                    <th><label for="currency">Para Birimi</label></th>
                    <td>
                        <select name="sl_ai_currency" id="currency">
                            <option value="TRY" <?php selected($currency, 'TRY'); ?>>TRY</option>
                            <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                            <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                            <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                        </select>
                        <p class="description">Raporlarda kullanılacak para birimi</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="auto_sync">Otomatik Senkronizasyon</label></th>
                    <td>
                        <input type="checkbox" name="sl_ai_auto_sync" id="auto_sync" 
                               value="1" <?php checked($auto_sync, 1); ?>>
                        <label for="auto_sync">Her 5 dakikada bir veri çek</label>
                        <p class="description">Google Ads ve Analytics verilerini otomatik senkronize et</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cache_ttl">Cache Süresi (saniye)</label></th>
                    <td>
                        <input type="number" name="sl_ai_cache_ttl" id="cache_ttl" 
                               value="<?php echo esc_attr($cache_ttl); ?>" 
                               min="60" max="3600" step="60">
                        <p class="description">API yanıtlarının cache'te kalma süresi (60-3600 saniye)</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="api-settings" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="openai_api_key">OpenAI API Key</label></th>
                    <td>
                        <input type="password" name="sl_ai_openai_api_key" id="openai_api_key" 
                               value="<?php echo esc_attr($openai_api_key); ?>"
                               class="regular-text">
                        <p class="description">OpenAI API erişimi için gerekli anahtar</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="google_api_key">Google API Key</label></th>
                    <td>
                        <input type="password" name="sl_ai_google_api_key" id="google_api_key" 
                               value="<?php echo esc_attr($google_api_key); ?>"
                               class="regular-text">
                        <p class="description">Google Analytics ve diğer Google servisleri için API anahtarı</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="google-ads-settings" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="dev_token">Developer Token</label></th>
                    <td>
                        <input type="password" name="sl_ai_google_dev_token" id="dev_token" 
                               value="<?php echo esc_attr($google_dev_token); ?>"
                               class="regular-text">
                        <p class="description">Google Ads API için developer token</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="client_id">Client ID</label></th>
                    <td>
                        <input type="text" name="sl_ai_google_client_id" id="client_id" 
                               value="<?php echo esc_attr($google_client_id); ?>"
                               class="regular-text">
                        <p class="description">Google Cloud Console'daki Client ID</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="client_secret">Client Secret</label></th>
                    <td>
                        <input type="password" name="sl_ai_google_client_secret" id="client_secret" 
                               value="<?php echo esc_attr($google_client_secret); ?>"
                               class="regular-text">
                        <p class="description">Google Cloud Console'daki Client Secret</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="refresh_token">Refresh Token</label></th>
                    <td>
                        <input type="password" name="sl_ai_google_refresh_token" id="refresh_token" 
                               value="<?php echo esc_attr($google_refresh_token); ?>"
                               class="regular-text">
                        <p class="description">OAuth2 için refresh token</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="advanced-settings" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="remove_data_on_uninstall">Kaldırma Ayarları</label></th>
                    <td>
                        <input type="checkbox" name="sl_ai_remove_data_on_uninstall" id="remove_data_on_uninstall" 
                               value="1" <?php checked($remove_data_on_uninstall, 1); ?>>
                        <label for="remove_data_on_uninstall">Eklenti kaldırıldığında TÜM verileri sil</label>
                        <p class="description"><strong>⚠️ Dikkat:</strong> Bu seçenek aktifken eklentiyi sildiğinizde tüm veritabanı tabloları, ayarlar ve kayıtlar silinecektir!</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enable_debug">Debug Modu</label></th>
                    <td>
                        <input type="checkbox" name="sl_ai_enable_debug" id="enable_debug" 
                               value="1" <?php checked($enable_debug, 1); ?>>
                        <label for="enable_debug">Debug loglarını aktif et</label>
                        <p class="description">Hata ayıklama için log kayıtlarını etkinleştirir</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cron_interval">Cron Aralığı</label></th>
                    <td>
                        <select name="sl_ai_cron_interval" id="cron_interval">
                            <option value="hourly" <?php selected($cron_interval, 'hourly'); ?>>Saatlik</option>
                            <option value="twicedaily" <?php selected($cron_interval, 'twicedaily'); ?>>Günde 2 kez</option>
                            <option value="daily" <?php selected($cron_interval, 'daily'); ?>>Günlük</option>
                            <option value="weekly" <?php selected($cron_interval, 'weekly'); ?>>Haftalık</option>
                        </select>
                        <p class="description">Otomatik senkronizasyon aralığı</p>
                    </td>
                </tr>
                
                <tr>
                    <th><label>Manuel Temizleme</label></th>
                    <td>
                        <div class="danger-zone" style="border: 2px solid #dc3232; padding: 15px; border-radius: 4px; background: #fff5f5;">
                            <h3 style="color: #dc3232; margin-top: 0;">⚠️ Tehlike Bölgesi</h3>
                            <p><strong>Bu işlem geri alınamaz!</strong> Tüm eklenti verilerini (tablolar, ayarlar, cache) kalıcı olarak silecektir.</p>
                            <a href="<?php echo esc_url($cleanup_url); ?>" 
                               class="button button-danger" 
                               style="background: #dc3232; border-color: #dc3232; color: white;"
                               onclick="return confirm('TÜM eklenti verilerini silmek istediğinize emin misiniz?\\n\\nBu işlem GERİ ALINAMAZ!\\n\\n• Tüm veritabanı tabloları\\n• Tüm ayarlar\\n• Tüm cache verileri\\n\\nsilinecektir.');">
                                Tüm Eklenti Verilerini Sil
                            </a>
                            <p class="description"><small>Eklentiyi kaldırmadan sadece verileri temizlemek için kullanın.</small></p>
                        </div>
                    </td>
                </tr>
                
                <tr>
                    <th>Plugin Bilgileri</th>
                    <td>
                        <table class="widefat" style="width: 100%;">
                            <tbody>
                                <tr>
                                    <td><strong>Versiyon:</strong></td>
                                    <td><?php echo get_option('sl_ai_version', '2.0.0'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Veritabanı Tabloları:</strong></td>
                                    <td>
                                        <?php
                                        global $wpdb;
                                        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}sl_ai_%'");
                                        echo count($tables) . ' tablo bulundu';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Son Güncelleme:</strong></td>
                                    <td><?php echo get_option('sl_ai_installed', 'Bilinmiyor'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <input type="submit" name="sl_ai_save_settings" class="button button-primary" value="Ayarları Kaydet">
        </p>
    </form>
</div>

<style>
    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
    .tab-content {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        border-top: none;
    }
    .button-danger:hover {
        background: #a00 !important;
        border-color: #a00 !important;
    }
    .form-table th {
        width: 200px;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');
        
        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');
        
        // Hide all tab content
        $('.tab-content').hide();
        
        // Show selected tab content
        var tabId = $(this).attr('href');
        $(tabId).show();
    });
    
    // Initialize first tab
    $('.nav-tab-active').trigger('click');
    
    // Show/hide API fields based on auto sync
    $('#auto_sync').on('change', function() {
        if ($(this).is(':checked')) {
            $('#cron_interval').prop('disabled', false);
        } else {
            $('#cron_interval').prop('disabled', true);
        }
    });
    
    // Trigger change event on page load
    $('#auto_sync').trigger('change');
    
    // Confirm before manual cleanup
    $('.button-danger').on('click', function(e) {
        if (!confirm('TÜM eklenti verilerini silmek istediğinize emin misiniz?\n\nBu işlem GERİ ALINAMAZ!')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>