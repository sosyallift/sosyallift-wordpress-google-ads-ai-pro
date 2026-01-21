<?php
namespace SosyalliftAIPro\Core;

class ApiClient {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $base_url = 'https://api.sosyallift.com/v1/';
    private $api_key = '';
    private $timeout = 30;
    private $retry_attempts = 3;
    
    protected function __construct() {
        $this->api_key = get_option('sl_ai_pro_api_key', '');
    }
    
    public function set_api_key(string $api_key): void {
        $this->api_key = $api_key;
    }
    
    public function get(string $endpoint, array $params = []): array {
        return $this->request('GET', $endpoint, $params);
    }
    
    public function post(string $endpoint, array $data = []): array {
        return $this->request('POST', $endpoint, [], $data);
    }
    
    public function put(string $endpoint, array $data = []): array {
        return $this->request('PUT', $endpoint, [], $data);
    }
    
    public function delete(string $endpoint): array {
        return $this->request('DELETE', $endpoint);
    }
    
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array {
        $url = $this->base_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => $this->get_headers(),
            'body' => !empty($data) ? json_encode($data) : null,
        ];
        
        $attempt = 0;
        
        do {
            $attempt++;
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                break;
            }
            
            if ($attempt >= $this->retry_attempts) {
                break;
            }
            
            // Exponential backoff
            sleep(pow(2, $attempt));
            
        } while ($attempt < $this->retry_attempts);
        
        if (is_wp_error($response)) {
            Logger::error('API request failed', [
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
        
        Logger::debug('API response', [
            'endpoint' => $endpoint,
            'status' => $status_code,
            'body' => $body
        ]);
        
        $response_data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('API response parse error', [
                'endpoint' => $endpoint,
                'response' => $body
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'code' => $status_code
            ];
        }
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'data' => $response_data,
            'code' => $status_code
        ];
    }
    
    private function get_headers(): array {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'SosyalliftAIPro/' . SL_AI_PRO_VERSION . '; WordPress/' . get_bloginfo('version'),
        ];
        
        if ($this->api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        return $headers;
    }
    
    public function analyze_keywords(array $keywords): array {
        return $this->post('keywords/analyze', [
            'keywords' => $keywords,
            'language' => get_locale(),
            'market' => 'TR'
        ]);
    }
    
    public function get_seo_data(string $url): array {
        return $this->post('seo/analyze', [
            'url' => $url
        ]);
    }
    
    public function get_competitor_data(string $domain): array {
        return $this->post('competitors/analyze', [
            'domain' => $domain
        ]);
    }
    
    public function detect_intent(string $query): array {
        return $this->post('intent/detect', [
            'query' => $query,
            'language' => get_locale()
        ]);
    }
    
    public function get_trends(string $keyword, string $period = '30d'): array {
        return $this->get('trends', [
            'keyword' => $keyword,
            'period' => $period
        ]);
    }
    
    public function test_connection(): array {
        $response = $this->get('health');
        
        if ($response['success']) {
            update_option('sl_ai_pro_api_connected', true);
            update_option('sl_ai_pro_api_last_test', time());
        } else {
            update_option('sl_ai_pro_api_connected', false);
        }
        
        return $response;
    }
    
    public function is_connected(): bool {
        return get_option('sl_ai_pro_api_connected', false);
    }
}<?php
namespace SosyalliftAIPro\Core;

class ApiClient {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $base_url = 'https://api.sosyallift.com/v1/';
    private $api_key = '';
    private $timeout = 30;
    private $retry_attempts = 3;
    
    protected function __construct() {
        $this->api_key = get_option('sl_ai_pro_api_key', '');
    }
    
    public function set_api_key(string $api_key): void {
        $this->api_key = $api_key;
    }
    
    public function get(string $endpoint, array $params = []): array {
        return $this->request('GET', $endpoint, $params);
    }
    
    public function post(string $endpoint, array $data = []): array {
        return $this->request('POST', $endpoint, [], $data);
    }
    
    public function put(string $endpoint, array $data = []): array {
        return $this->request('PUT', $endpoint, [], $data);
    }
    
    public function delete(string $endpoint): array {
        return $this->request('DELETE', $endpoint);
    }
    
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array {
        $url = $this->base_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => $this->get_headers(),
            'body' => !empty($data) ? json_encode($data) : null,
        ];
        
        $attempt = 0;
        
        do {
            $attempt++;
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                break;
            }
            
            if ($attempt >= $this->retry_attempts) {
                break;
            }
            
            // Exponential backoff
            sleep(pow(2, $attempt));
            
        } while ($attempt < $this->retry_attempts);
        
        if (is_wp_error($response)) {
            Logger::error('API request failed', [
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
        
        Logger::debug('API response', [
            'endpoint' => $endpoint,
            'status' => $status_code,
            'body' => $body
        ]);
        
        $response_data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('API response parse error', [
                'endpoint' => $endpoint,
                'response' => $body
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'code' => $status_code
            ];
        }
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'data' => $response_data,
            'code' => $status_code
        ];
    }
    
    private function get_headers(): array {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'SosyalliftAIPro/' . SL_AI_PRO_VERSION . '; WordPress/' . get_bloginfo('version'),
        ];
        
        if ($this->api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        return $headers;
    }
    
    public function analyze_keywords(array $keywords): array {
        return $this->post('keywords/analyze', [
            'keywords' => $keywords,
            'language' => get_locale(),
            'market' => 'TR'
        ]);
    }
    
    public function get_seo_data(string $url): array {
        return $this->post('seo/analyze', [
            'url' => $url
        ]);
    }
    
    public function get_competitor_data(string $domain): array {
        return $this->post('competitors/analyze', [
            'domain' => $domain
        ]);
    }
    
    public function detect_intent(string $query): array {
        return $this->post('intent/detect', [
            'query' => $query,
            'language' => get_locale()
        ]);
    }
    
    public function get_trends(string $keyword, string $period = '30d'): array {
        return $this->get('trends', [
            'keyword' => $keyword,
            'period' => $period
        ]);
    }
    
    public function test_connection(): array {
        $response = $this->get('health');
        
        if ($response['success']) {
            update_option('sl_ai_pro_api_connected', true);
            update_option('sl_ai_pro_api_last_test', time());
        } else {
            update_option('sl_ai_pro_api_connected', false);
        }
        
        return $response;
    }
    
    public function is_connected(): bool {
        return get_option('sl_ai_pro_api_connected', false);
    }
}<?php
namespace SosyalliftAIPro\Core;

class ApiClient {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $base_url = 'https://api.sosyallift.com/v1/';
    private $api_key = '';
    private $timeout = 30;
    private $retry_attempts = 3;
    
    protected function __construct() {
        $this->api_key = get_option('sl_ai_pro_api_key', '');
    }
    
    public function set_api_key(string $api_key): void {
        $this->api_key = $api_key;
    }
    
    public function get(string $endpoint, array $params = []): array {
        return $this->request('GET', $endpoint, $params);
    }
    
    public function post(string $endpoint, array $data = []): array {
        return $this->request('POST', $endpoint, [], $data);
    }
    
    public function put(string $endpoint, array $data = []): array {
        return $this->request('PUT', $endpoint, [], $data);
    }
    
    public function delete(string $endpoint): array {
        return $this->request('DELETE', $endpoint);
    }
    
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array {
        $url = $this->base_url . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $args = [
            'method' => $method,
            'timeout' => $this->timeout,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => $this->get_headers(),
            'body' => !empty($data) ? json_encode($data) : null,
        ];
        
        $attempt = 0;
        
        do {
            $attempt++;
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                break;
            }
            
            if ($attempt >= $this->retry_attempts) {
                break;
            }
            
            // Exponential backoff
            sleep(pow(2, $attempt));
            
        } while ($attempt < $this->retry_attempts);
        
        if (is_wp_error($response)) {
            Logger::error('API request failed', [
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
        
        Logger::debug('API response', [
            'endpoint' => $endpoint,
            'status' => $status_code,
            'body' => $body
        ]);
        
        $response_data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('API response parse error', [
                'endpoint' => $endpoint,
                'response' => $body
            ]);
            
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'code' => $status_code
            ];
        }
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'data' => $response_data,
            'code' => $status_code
        ];
    }
    
    private function get_headers(): array {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'SosyalliftAIPro/' . SL_AI_PRO_VERSION . '; WordPress/' . get_bloginfo('version'),
        ];
        
        if ($this->api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        return $headers;
    }
    
    public function analyze_keywords(array $keywords): array {
        return $this->post('keywords/analyze', [
            'keywords' => $keywords,
            'language' => get_locale(),
            'market' => 'TR'
        ]);
    }
    
    public function get_seo_data(string $url): array {
        return $this->post('seo/analyze', [
            'url' => $url
        ]);
    }
    
    public function get_competitor_data(string $domain): array {
        return $this->post('competitors/analyze', [
            'domain' => $domain
        ]);
    }
    
    public function detect_intent(string $query): array {
        return $this->post('intent/detect', [
            'query' => $query,
            'language' => get_locale()
        ]);
    }
    
    public function get_trends(string $keyword, string $period = '30d'): array {
        return $this->get('trends', [
            'keyword' => $keyword,
            'period' => $period
        ]);
    }
    
    public function test_connection(): array {
        $response = $this->get('health');
        
        if ($response['success']) {
            update_option('sl_ai_pro_api_connected', true);
            update_option('sl_ai_pro_api_last_test', time());
        } else {
            update_option('sl_ai_pro_api_connected', false);
        }
        
        return $response;
    }
    
    public function is_connected(): bool {
        return get_option('sl_ai_pro_api_connected', false);
    }
}
