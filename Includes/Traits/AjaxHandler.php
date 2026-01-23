<?php
namespace SosyalliftAIPro\Includes\Traits;

trait AjaxHandler {
    protected function verify_ajax_nonce(string $action = 'ajax'): void {
        $nonce = $_REQUEST['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'sl_ai_pro_' . $action . '_nonce')) {
            wp_send_json_error([
                'message' => 'Güvenlik doğrulaması başarısız'
            ], 403);
        }
    }
    
    protected function check_ajax_permission(string $capability = 'manage_options'): void {
        if (!current_user_can($capability)) {
            wp_send_json_error([
                'message' => 'Bu işlem için yetkiniz yok'
            ], 403);
        }
    }
    
    protected function sanitize_ajax_input(array $data): array {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_ajax_input($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    protected function validate_ajax_required(array $data, array $required_fields): bool {
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}