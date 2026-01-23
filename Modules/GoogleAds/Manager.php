<?php
namespace SosyalliftAIPro\Modules\GoogleAds;

use SosyalliftAIPro\Includes\Traits\Singleton;
use SosyalliftAIPro\Core\ApiClient;
use SosyalliftAIPro\Core\Logger;

/**
 * Google Ads Modülü Yönetici Sınıfı
 * Google Ads API entegrasyonu ve reklam veri yönetimi
 */
class Manager {
    use Singleton;

    const VERSION = '2.0.0';
    const API_VERSION = 'v16';
    const GOOGLE_ADS_BASE_URL = 'https://googleads.googleapis.com/';

    private $settings;
    private $api_client;
    private $endpoints = [];
    private $is_active = false;

    protected function __construct() {
        $this->load_settings();
        $this->setup_api_client();
        $this->check_requirements();
    }

    private function load_settings(): void {
        $this->settings = get_option('sl_ai_pro_google_ads_settings', [
            'developer_token'   => '',
            'client_id'         => '',
            'client_secret'     => '',
            'refresh_token'     => '',
            'customer_id'       => '',
            'manager_id'        => '',
            'auto_sync'         => true,
            'sync_interval'     => 3600,
            'last_sync'         => 0,
            'connected'         => false,
            'campaign_filters'  => [],
            'keyword_filters'   => [],
        ]);

        $this->is_active = !empty($this->settings['developer_token']) && 
                          !empty($this->settings['client_id']) && 
                          $this->settings['connected'];
    }

    private function setup_api_client(): void {
        $this->api_client = ApiClient::get_instance();
        
        // ApiClient'ın register_endpoint() metodu YOK
        // Kendi endpoint yapılandırmamızı kullanacağız
        $this->endpoints['google_ads'] = [
            'base_url'  => self::GOOGLE_ADS_BASE_URL . self::API_VERSION . '/',
            'headers'   => [
                'Authorization' => 'Bearer ' . ($this->settings['access_token'] ?? ''),
                'developer-token' => $this->settings['developer_token'] ?? '',
                'login-customer-id' => $this->settings['customer_id'] ?? '',
            ],
            'timeout'   => 30,
            'retry'     => true,
            'max_retries' => 3,
        ];
    }

    private function check_requirements(): bool {
        $errors = [];
        $required_extensions = ['curl', 'json', 'openssl'];
        
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = "PHP {$ext} extension gereklidir";
            }
        }

        if (!function_exists('wp_remote_post')) {
            $errors[] = "WordPress HTTP API kullanılamıyor";
        }

        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 134217728) {
            $errors[] = "Minimum 128MB memory limit gereklidir";
        }

        if (!empty($errors)) {
            Logger::error('Google Ads modülü gereksinimleri karşılanmadı', [
                'errors' => $errors,
                'settings' => $this->settings
            ]);
            $this->is_active = false;
            return false;
        }

        return true;
    }

    /**
     * Google Ads API isteği yapar
     */
    private function google_ads_request(string $method, string $endpoint, array $params = [], array $data = []): array {
        $endpoint_config = $this->endpoints['google_ads'] ?? [];
        $url = $endpoint_config['base_url'] . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method'  => $method,
            'timeout' => $endpoint_config['timeout'] ?? 30,
            'headers' => $endpoint_config['headers'] ?? [],
            'body'    => !empty($data) ? json_encode($data) : null,
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            Logger::error('Google Ads API request failed', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'code' => 0
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'data' => $response_data,
            'code' => $status_code
        ];
    }

    public function register(): void {
        if (!$this->is_active()) {
            return;
        }

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_sl_ai_pro_google_ads_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_sl_ai_pro_google_ads_sync', [$this, 'ajax_sync_data']);
        add_action('sl_ai_pro_cron_sync', [$this, 'cron_sync']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        Logger::info('Google Ads modülü kaydedildi', [
            'version' => self::VERSION,
            'active' => $this->is_active
        ]);
    }

    public function is_active(): bool {
        return $this->is_active;
    }

    public function add_admin_menu(): void {
        add_submenu_page(
            'sl_ai_pro_dashboard',
            'Google Ads Yönetimi',
            'Google Ads',
            'manage_options',
            'sl-ai-pro-google-ads',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok');
        }
        ?>
        <div class="wrap">
            <h1>Google Ads Yönetimi</h1>
            <div class="card">
                <h2>API Bağlantı Durumu</h2>
                <p>Google Ads API bağlantısı: 
                    <span style="color: <?php echo $this->is_active ? 'green' : 'red'; ?>">
                        <?php echo $this->is_active ? 'AKTİF' : 'PASİF'; ?>
                    </span>
                </p>
                <button id="test-connection" class="button button-primary">Bağlantıyı Test Et</button>
                <button id="sync-data" class="button">Verileri Senkronize Et</button>
            </div>
        </div>
        <?php
    }

    public function cron_sync(): void {
        if (!$this->is_active()) {
            return;
        }

        $last_sync = $this->settings['last_sync'] ?? 0;
        $sync_interval = $this->settings['sync_interval'] ?? 3600;

        if (time() - $last_sync < $sync_interval) {
            return;
        }

        try {
            Logger::info('Google Ads cron sync başlatıldı');
            
            // Kampanyaları senkronize et
            $this->sync_campaigns();
            
            // Ayarları güncelle
            $this->settings['last_sync'] = time();
            update_option('sl_ai_pro_google_ads_settings', $this->settings);
            
            Logger::info('Google Ads cron sync tamamlandı', [
                'synced_at' => time()
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Google Ads cron sync hatası', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sync_campaigns(): void {
        global $wpdb;
        
        try {
            // Google Ads API'den kampanya verilerini çek
            $response = $this->google_ads_request(
                'GET', 
                'customers/' . $this->settings['customer_id'] . '/campaigns'
            );
            
            if ($response['success'] && isset($response['data']['campaigns'])) {
                foreach ($response['data']['campaigns'] as $campaign) {
                    $wpdb->replace(
                        $wpdb->prefix . 'sl_ai_google_ads_campaigns',
                        [
                            'campaign_id'       => $campaign['id'] ?? '',
                            'name'              => $campaign['name'] ?? '',
                            'status'            => $campaign['status'] ?? 'UNKNOWN',
                            'last_updated'      => current_time('mysql'),
                        ],
                        ['%s', '%s', '%s', '%s']
                    );
                }
                Logger::info('Kampanyalar senkronize edildi', [
                    'count' => count($response['data']['campaigns'])
                ]);
            }
            
        } catch (\Exception $e) {
            throw new \Exception('Kampanya senkronizasyonu başarısız: ' . $e->getMessage());
        }
    }

    public function ajax_test_connection(): void {
        check_ajax_referer('sl_ai_pro_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }

        try {
            // Google Ads API bağlantı testi
            $test_result = $this->google_ads_request(
                'GET', 
                'customers/' . $this->settings['customer_id'],
                ['fields' => 'customer.id,customer.descriptive_name']
            );

            if ($test_result['success']) {
                $this->settings['connected'] = true;
                update_option('sl_ai_pro_google_ads_settings', $this->settings);
                $this->is_active = true;
                
                wp_send_json_success([
                    'message' => 'Google Ads API bağlantısı başarılı',
                    'customer' => $test_result['data']['customer']['descriptiveName'] ?? 'Bilinmeyen'
                ]);
            } else {
                wp_send_json_error('API yanıtı geçersiz: ' . ($test_result['error'] ?? ''));
            }
            
        } catch (\Exception $e) {
            wp_send_json_error('Bağlantı hatası: ' . $e->getMessage());
        }
    }

    public function ajax_sync_data(): void {
        check_ajax_referer('sl_ai_pro_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkiniz yok');
        }

        try {
            $this->cron_sync();
            wp_send_json_success('Veri senkronizasyonu tamamlandı');
            
        } catch (\Exception $e) {
            wp_send_json_error('Senkronizasyon hatası: ' . $e->getMessage());
        }
    }

    public function enqueue_admin_scripts($hook): void {
        if (strpos($hook, 'sl-ai-pro-google-ads') === false) {
            return;
        }

        wp_enqueue_script(
            'sl-ai-pro-google-ads-admin',
            SL_AI_PRO_URL . 'assets/js/google-ads-admin.js',
            ['jquery'],
            self::VERSION,
            true
        );

        wp_localize_script('sl-ai-pro-google-ads-admin', 'sl_ai_pro_google_ads', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sl_ai_pro_ajax'),
        ]);
    }

    public function get_module_info(): array {
        return [
            'name'        => 'Google Ads',
            'version'     => self::VERSION,
            'description' => 'Google Ads API entegrasyonu ve reklam yönetimi',
            'active'      => $this->is_active(),
            'settings'    => [
                'connected'  => $this->settings['connected'] ?? false,
                'last_sync'  => $this->settings['last_sync'] ?? 0,
                'auto_sync'  => $this->settings['auto_sync'] ?? true,
            ]
        ];
    }
}