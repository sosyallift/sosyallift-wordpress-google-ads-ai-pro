<?php
namespace SosyalliftAIPro\Core;

class Security {
    use \SosyalliftAIPro\Includes\Traits\Singleton;

    private $nonce_actions = [
        'dashboard'     => 'sl_ai_pro_dashboard_nonce',
        'settings'      => 'sl_ai_pro_settings_nonce',
        'export'        => 'sl_ai_pro_export_nonce',
        'import'        => 'sl_ai_pro_import_nonce',
        'license'       => 'sl_ai_pro_license_nonce',
        'ajax'          => 'sl_ai_pro_ajax_nonce',
    ];

    protected function __construct() {
        // Constructor is private for singleton
    }

    public function init(): void {
        add_action('init', [$this, 'add_security_headers']);
        add_filter('upload_mimes', [$this, 'restrict_upload_mimes']);
        add_action('admin_init', [$this, 'check_admin_referer']);
    }

    public function add_security_headers(): void {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }

    public function create_nonce(string $action): string {
        return wp_create_nonce($this->get_nonce_action($action));
    }

    public function verify_nonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, $this->get_nonce_action($action));
    }

    public function verify_ajax_nonce(): void {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        
        if (!$this->verify_nonce($nonce, 'ajax')) {
            $this->send_security_error('Invalid nonce');
        }
    }

    public function validate_input(array $data, array $rules): array {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $field_errors = [];

            foreach (explode('|', $rule) as $single_rule) {
                if (strpos($single_rule, ':') !== false) {
                    [$rule_name, $rule_param] = explode(':', $single_rule, 2);
                } else {
                    $rule_name = $single_rule;
                    $rule_param = null;
                }

                $method = 'validate_' . $rule_name;
                if (method_exists($this, $method)) {
                    if (!$this->$method($value, $rule_param)) {
                        $field_errors[] = $this->get_validation_message($rule_name, $field, $rule_param);
                    }
                }
            }

            if (empty($field_errors)) {
                $validated[$field] = $this->sanitize_field($value, $rule);
            } else {
                $errors[$field] = $field_errors;
            }
        }

        if (!empty($errors)) {
            throw new \SosyalliftAIPro\Includes\Exceptions\ValidationException(
                'Validation failed',
                $errors
            );
        }

        return $validated;
    }

    private function validate_required($value): bool {
        return !empty($value) || $value === '0' || $value === 0;
    }

    private function validate_email($value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validate_url($value): bool {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validate_numeric($value): bool {
        return is_numeric($value);
    }

    private function validate_min($value, $param): bool {
        return $value >= $param;
    }

    private function validate_max($value, $param): bool {
        return $value <= $param;
    }

    private function validate_length($value, $param): bool {
        return strlen($value) <= $param;
    }

    private function validate_regex($value, $pattern): bool {
        return preg_match($pattern, $value) === 1;
    }

    private function sanitize_field($value, string $rule): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize_deep'], $value);
        }

        if (strpos($rule, 'email') !== false) {
            return sanitize_email($value);
        }

        if (strpos($rule, 'url') !== false) {
            return esc_url_raw($value);
        }

        if (strpos($rule, 'text') !== false) {
            return sanitize_text_field($value);
        }

        if (strpos($rule, 'textarea') !== false) {
            return sanitize_textarea_field($value);
        }

        if (strpos($rule, 'key') !== false) {
            return preg_replace('/[^a-zA-Z0-9_\-]/', '', $value);
        }

        return $this->sanitize_deep($value);
    }

    private function sanitize_deep($value): mixed {
        if (is_array($value)) {
            return array_map([$this, 'sanitize_deep'], $value);
        }

        if (is_string($value)) {
            return wp_kses_post($value);
        }

        return $value;
    }

    public function check_file_upload(array $file): array {
        $errors = [];

        // Check PHP errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->get_upload_error($file['error']);
            return ['success' => false, 'errors' => $errors];
        }

        // Check file size (max 10MB)
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds 10MB limit';
        }

        // Check file type
        $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            $errors[] = 'Invalid file type. Only CSV files are allowed';
        }

        // Check file extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'csv') {
            $errors[] = 'File must have .csv extension';
        }

        // Scan for malware
        if (function_exists('clamav_scan_file')) {
            $scan_result = clamav_scan_file($file['tmp_name']);
            if ($scan_result !== 0) {
                $errors[] = 'File failed security scan';
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Move to secure location
        $upload_dir = wp_upload_dir();
        $secure_dir = $upload_dir['basedir'] . '/sl_ai_pro_uploads/';
        
        if (!file_exists($secure_dir)) {
            wp_mkdir_p($secure_dir);
            file_put_contents($secure_dir . '.htaccess', 'Deny from all');
        }

        $filename = 'upload_' . wp_hash($file['name'] . time()) . '.csv';
        $destination = $secure_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'errors' => ['Failed to save uploaded file']];
        }

        return [
            'success'   => true,
            'path'      => $destination,
            'filename'  => $filename,
            'size'      => $file['size'],
            'mime'      => $mime,
        ];
    }

    public function encrypt_data(string $data, string $key = null): string {
        if ($key === null) {
            $key = $this->get_encryption_key();
        }

        $method = 'aes-256-gcm';
        $iv = random_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv, $tag);
        
        return base64_encode($iv . $tag . $encrypted);
    }

    public function decrypt_data(string $encrypted, string $key = null): string {
        if ($key === null) {
            $key = $this->get_encryption_key();
        }

        $data = base64_decode($encrypted);
        
        $method = 'aes-256-gcm';
        $iv_length = openssl_cipher_iv_length($method);
        $tag_length = 16;
        
        $iv = substr($data, 0, $iv_length);
        $tag = substr($data, $iv_length, $tag_length);
        $ciphertext = substr($data, $iv_length + $tag_length);
        
        return openssl_decrypt($ciphertext, $method, $key, 0, $iv, $tag);
    }

    private function get_encryption_key(): string {
        $key = get_option('sl_ai_pro_encryption_key');
        
        if (!$key) {
            $key = bin2hex(random_bytes(32));
            update_option('sl_ai_pro_encryption_key', $key);
        }
        
        return $key;
    }

    public function restrict_upload_mimes(array $mimes): array {
        // Only allow specific file types for our plugin
        if (current_user_can('manage_options')) {
            $mimes['csv'] = 'text/csv';
            $mimes['json'] = 'application/json';
        }
        
        return $mimes;
    }

    public function check_admin_referer(): void {
        $action = $_REQUEST['action'] ?? '';
        
        if (strpos($action, 'sl_ai_pro_') === 0) {
            check_admin_referer($action, '_wpnonce');
        }
    }

    private function get_nonce_action(string $action): string {
        return $this->nonce_actions[$action] ?? 'sl_ai_pro_' . $action . '_nonce';
    }

    private function get_upload_error(int $error_code): string {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload',
        ];

        return $errors[$error_code] ?? 'Unknown upload error';
    }

    private function get_validation_message(string $rule, string $field, $param = null): string {
        $messages = [
            'required'  => sprintf(__('%s is required', 'sosyallift-ai-pro'), $field),
            'email'     => sprintf(__('%s must be a valid email address', 'sosyallift-ai-pro'), $field),
            'url'       => sprintf(__('%s must be a valid URL', 'sosyallift-ai-pro'), $field),
            'numeric'   => sprintf(__('%s must be a number', 'sosyallift-ai-pro'), $field),
            'min'       => sprintf(__('%s must be at least %s', 'sosyallift-ai-pro'), $field, $param),
            'max'       => sprintf(__('%s must be at most %s', 'sosyallift-ai-pro'), $field, $param),
            'length'    => sprintf(__('%s must not exceed %s characters', 'sosyallift-ai-pro'), $field, $param),
            'regex'     => sprintf(__('%s format is invalid', 'sosyallift-ai-pro'), $field),
        ];

        return $messages[$rule] ?? sprintf(__('%s validation failed', 'sosyallift-ai-pro'), $field);
    }

    private function send_security_error(string $message): void {
        if (wp_doing_ajax()) {
            wp_send_json_error(['message' => $message], 403);
        } else {
            wp_die($message, 'Security Error', ['response' => 403]);
        }
    }
}
