<?php
namespace SL_AI\Core;
class Security {
    public static function check_nonce($action = 'ajax') {
        $nonce = $_POST['nonce'] ?? '';
        return wp_verify_nonce($nonce, "sl_ai_{$action}_nonce");
    }
    public static function create_nonce($action = 'ajax') {
        return wp_create_nonce("sl_ai_{$action}_nonce");
    }
    public static function validate_input($data, $rules) {
        $clean = [];
        foreach ($rules as $field => $type) {
            $value = $data[$field] ?? '';
            switch ($type) {
                case 'email':
                    $clean[$field] = sanitize_email($value);
                    break;
                case 'url':
                    $clean[$field] = esc_url_raw($value);
                    break;
                case 'text':
                    $clean[$field] = sanitize_text_field($value);
                    break;
                case 'textarea':
                    $clean[$field] = sanitize_textarea_field($value);
                    break;
                case 'html':
                    $clean[$field] = wp_kses_post($value);
                    break;
                default:
                    $clean[$field] = sanitize_text_field($value);
            }
        }
        return $clean;
    }
    public static function check_file_upload($file) {
        $errors = [];
        // Max 10MB
        $max_size = 10 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            $errors[] = 'Dosya boyutu 10MB\'dan büyük';
        }
        // Sadece CSV ve JSON
        $allowed_types = ['text/csv', 'application/json', 'text/plain'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed_types)) {
            $errors[] = 'Sadece CSV ve JSON dosyaları yüklenebilir';
        }
        return empty($errors) ? true : $errors;
    }
}
