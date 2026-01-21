<?php
namespace SosyalliftAIPro\Core;

class LicenseManager {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $api_url = 'https://sosyallift.com/api/v1/license/';
    private $license_key = '';
    private $license_data = null;
    
    protected function __construct() {
        $this->license_key = get_option('sl_ai_pro_license_key', '');
        $this->license_data = get_option('sl_ai_pro_license_data', null);
    }
    
    public function init(): void {
        add_action('admin_init', [$this, 'check_license_status']);
    }
    
    public function activate_license(string $license_key, string $email): array {
        $response = $this->api_request('activate', [
            'license_key' => $license_key,
            'email' => $email,
            'domain' => site_url(),
            'product_id' => 'sosyallift-ai-pro'
        ]);
        
        if ($response['success']) {
            $this->license_key = $license_key;
            $this->license_data = $response['data'];
            
            update_option('sl_ai_pro_license_key', $license_key);
            update_option('sl_ai_pro_license_data', $response['data']);
            update_option('sl_ai_pro_license_activated', time());
            
            Logger::info('License activated', [
                'email' => $email,
                'domain' => site_url()
            ]);
        }
        
        return $response;
    }
    
    public function deactivate_license(): array {
        if (!$this->license_key) {
            return ['success' => false, 'message' => 'No active license'];
        }
        
        $response = $this->api_request('deactivate', [
            'license_key' => $this->license_key,
            'domain' => site_url()
        ]);
        
        if ($response['success']) {
            delete_option('sl_ai_pro_license_key');
            delete_option('sl_ai_pro_license_data');
            delete_option('sl_ai_pro_license_activated');
            
            $this->license_key = '';
            $this->license_data = null;
            
            Logger::info('License deactivated');
        }
        
        return $response;
    }
    
    public function verify_license(): array {
        if (!$this->license_key) {
            return ['success' => false, 'message' => 'No license key'];
        }
        
        $response = $this->api_request('verify', [
            'license_key' => $this->license_key,
            'domain' => site_url()
        ]);
        
        if ($response['success']) {
            $this->license_data = $response['data'];
            update_option('sl_ai_pro_license_data', $response['data']);
            update_option('sl_ai_pro_license_last_check', time());
            
            // Update license status
            update_option('sl_ai_pro_license_status', $response['data']['status']);
            
            Logger::debug('License verified', ['status' => $response['data']['status']]);
        } else {
            // License verification failed
            update_option('sl_ai_pro_license_status', 'invalid');
            Logger::warning('License verification failed', $response);
        }
        
        return $response;
    }
    
    public function verify_license_async(): void {
        $last_check = get_option('sl_ai_pro_license_last_check', 0);
        
        // Her 12 saatte bir kontrol et
        if (time() - $last_check > 43200) {
            $this->verify_license();
        }
    }
    
    public function check_license_status(): void {
        $status = $this->get_license_status();
        
        if ($status === 'expired' || $status === 'invalid') {
            add_action('admin_notices', [$this, 'show_license_notice']);
        }
    }
    
    public function show_license_notice(): void {
        $status = $this->get_license_status();
        
        if ($status === 'expired') {
            $message = __('Sosyallift AI Pro lisansınızın süresi doldu. Lütfen yenileyin.', 'sosyallift-ai-pro');
            $class = 'notice-error';
        } elseif ($status === 'invalid') {
            $message = __('Sosyallift AI Pro lisansınız geçersiz. Lütfen lisansınızı kontrol edin.', 'sosyallift-ai-pro');
            $class = 'notice-error';
        } else {
            return;
        }
        
        ?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p><?php echo esc_html($message); ?> 
                <a href="<?php echo admin_url('admin.php?page=sosyallift-ai-pro-license'); ?>">
                    <?php _e('Lisans Sayfası', 'sosyallift-ai-pro'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    public function get_license_status(): string {
        if (!$this->license_data) {
            return 'inactive';
        }
        
        return $this->license_data['status'] ?? 'invalid';
    }
    
    public function get_license_data(): ?array {
        return $this->license_data;
    }
    
    public function is_valid(): bool {
        $status = $this->get_license_status();
        return in_array($status, ['active', 'valid']);
    }
    
    public function is_expired(): bool {
        return $this->get_license_status() === 'expired';
    }
    
    public function get_expiry_date(): ?string {
        return $this->license_data['expires_at'] ?? null;
    }
    
    public function get_remaining_days(): ?int {
        $expiry_date = $this->get_expiry_date();
        
        if (!$expiry_date) {
            return null;
        }
        
        $expiry = strtotime($expiry_date);
        $now = time();
        
        $diff = $expiry - $now;
        
        return max(0, floor($diff / DAY_IN_SECONDS));
    }
    
    private function api_request(string $endpoint, array $data = []): array {
        $url = $this->api_url . $endpoint;
        
        $args = [
            'body' => $data,
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'SosyalliftAIPro/' . SL_AI_PRO_VERSION
            ],
        ];
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            Logger::error('License API request failed', [
                'endpoint' => $endpoint,
                'error' => $response->get_error_message()
            ]);
            
            return [
                'success' => false,
                'message' => 'API connection failed: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('License API response parse error', [
                'endpoint' => $endpoint,
                'response' => $body
            ]);
            
            return [
                'success' => false,
                'message' => 'Invalid API response'
            ];
        }
        
        return $data;
    }
    
    public function get_features(): array {
        if (!$this->is_valid()) {
            return $this->get_free_features();
        }
        
        return $this->license_data['features'] ?? $this->get_free_features();
    }
    
    private function get_free_features(): array {
        return [
            'basic_analysis' => true,
            'keyword_tracking' => true,
            'seo_monitoring' => true,
            'alerts' => true,
            'basic_reports' => true,
            'intent_detection' => false,
            'competitor_analysis' => false,
            'predictive_analytics' => false,
            'advanced_automation' => false,
            'white_label' => false,
        ];
    }
    
    public function has_feature(string $feature): bool {
        $features = $this->get_features();
        return $features[$feature] ?? false;
    }
}
