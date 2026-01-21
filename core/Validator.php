<?php
namespace SosyalliftAIPro\Core;

class Validator {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    public function validate(array $data, array $rules): array {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $field_rules) {
            $value = $data[$field] ?? null;
            $field_errors = [];
            
            foreach ($field_rules as $rule) {
                if (is_string($rule)) {
                    if (strpos($rule, ':') !== false) {
                        [$rule_name, $rule_param] = explode(':', $rule, 2);
                    } else {
                        $rule_name = $rule;
                        $rule_param = null;
                    }
                } elseif (is_callable($rule)) {
                    $rule_name = 'callback';
                } else {
                    continue;
                }
                
                $method = 'validate_' . $rule_name;
                
                if (method_exists($this, $method)) {
                    if (!$this->$method($value, $rule_param)) {
                        $field_errors[] = $this->get_error_message($rule_name, $field, $rule_param);
                    }
                } elseif ($rule_name === 'callback' && is_callable($rule)) {
                    if (!$rule($value)) {
                        $field_errors[] = "Validation failed for {$field}";
                    }
                }
            }
            
            if (empty($field_errors)) {
                $validated[$field] = $this->sanitize($value, $field_rules);
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
        return !empty($value) || $value === 0 || $value === '0';
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
    
    private function validate_integer($value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    private function validate_float($value): bool {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
    
    private function validate_min($value, $param): bool {
        if (!is_numeric($value)) {
            return false;
        }
        return $value >= $param;
    }
    
    private function validate_max($value, $param): bool {
        if (!is_numeric($value)) {
            return false;
        }
        return $value <= $param;
    }
    
    private function validate_between($value, $param): bool {
        if (!is_numeric($value)) {
            return false;
        }
        
        [$min, $max] = explode(',', $param);
        return $value >= $min && $value <= $max;
    }
    
    private function validate_length($value, $param): bool {
        return strlen((string) $value) <= $param;
    }
    
    private function validate_min_length($value, $param): bool {
        return strlen((string) $value) >= $param;
    }
    
    private function validate_max_length($value, $param): bool {
        return strlen((string) $value) <= $param;
    }
    
    private function validate_in($value, $param): bool {
        $allowed = explode(',', $param);
        return in_array($value, $allowed);
    }
    
    private function validate_not_in($value, $param): bool {
        $disallowed = explode(',', $param);
        return !in_array($value, $disallowed);
    }
    
    private function validate_regex($value, $pattern): bool {
        return preg_match($pattern, $value) === 1;
    }
    
    private function validate_array($value): bool {
        return is_array($value);
    }
    
    private function validate_boolean($value): bool {
        $acceptable = [true, false, 0, 1, '0', '1', 'true', 'false'];
        return in_array($value, $acceptable, true);
    }
    
    private function validate_date($value): bool {
        return strtotime($value) !== false;
    }
    
    private function validate_date_format($value, $format): bool {
        $date = \DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
    
    private function validate_json($value): bool {
        if (!is_string($value)) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    private function validate_ip($value): bool {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    private function validate_ipv4($value): bool {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
    
    private function validate_ipv6($value): bool {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
    
    private function sanitize($value, array $rules) {
        if (is_array($value)) {
            return array_map([$this, 'sanitize_value'], $value);
        }
        
        return $this->sanitize_value($value, $rules);
    }
    
    private function sanitize_value($value, array $rules = []) {
        if (is_array($value)) {
            return $value;
        }
        
        $value = (string) $value;
        
        // Apply sanitization based on rules
        if (in_array('email', $rules)) {
            return sanitize_email($value);
        }
        
        if (in_array('url', $rules)) {
            return esc_url_raw($value);
        }
        
        if (in_array('text', $rules) || in_array('string', $rules)) {
            return sanitize_text_field($value);
        }
        
        if (in_array('textarea', $rules)) {
            return sanitize_textarea_field($value);
        }
        
        if (in_array('html', $rules)) {
            return wp_kses_post($value);
        }
        
        if (in_array('key', $rules) || in_array('slug', $rules)) {
            return sanitize_key($value);
        }
        
        if (in_array('title', $rules)) {
            return sanitize_title($value);
        }
        
        // Default sanitization
        return sanitize_text_field($value);
    }
    
    private function get_error_message(string $rule, string $field, $param = null): string {
        $messages = [
            'required'      => sprintf(__('%s alanı zorunludur', 'sosyallift-ai-pro'), $field),
            'email'         => sprintf(__('%s geçerli bir email adresi olmalıdır', 'sosyallift-ai-pro'), $field),
            'url'           => sprintf(__('%s geçerli bir URL olmalıdır', 'sosyallift-ai-pro'), $field),
            'numeric'       => sprintf(__('%s sayısal bir değer olmalıdır', 'sosyallift-ai-pro'), $field),
            'integer'       => sprintf(__('%s tam sayı olmalıdır', 'sosyallift-ai-pro'), $field),
            'float'         => sprintf(__('%s ondalıklı sayı olmalıdır', 'sosyallift-ai-pro'), $field),
            'min'           => sprintf(__('%s en az %s olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'max'           => sprintf(__('%s en fazla %s olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'between'       => sprintf(__('%s %s ile %s arasında olmalıdır', 'sosyallift-ai-pro'), $field, ...explode(',', $param)),
            'length'        => sprintf(__('%s uzunluğu %s karakter olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'min_length'    => sprintf(__('%s en az %s karakter olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'max_length'    => sprintf(__('%s en fazla %s karakter olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'in'            => sprintf(__('%s geçerli bir değer değil', 'sosyallift-ai-pro'), $field),
            'not_in'        => sprintf(__('%s izin verilmeyen bir değer', 'sosyallift-ai-pro'), $field),
            'regex'         => sprintf(__('%s formatı geçersiz', 'sosyallift-ai-pro'), $field),
            'array'         => sprintf(__('%s dizi olmalıdır', 'sosyallift-ai-pro'), $field),
            'boolean'       => sprintf(__('%s true veya false olmalıdır', 'sosyallift-ai-pro'), $field),
            'date'          => sprintf(__('%s geçerli bir tarih olmalıdır', 'sosyallift-ai-pro'), $field),
            'date_format'   => sprintf(__('%s %s formatında olmalıdır', 'sosyallift-ai-pro'), $field, $param),
            'json'          => sprintf(__('%s geçerli JSON formatında olmalıdır', 'sosyallift-ai-pro'), $field),
            'ip'            => sprintf(__('%s geçerli bir IP adresi olmalıdır', 'sosyallift-ai-pro'), $field),
            'ipv4'          => sprintf(__('%s geçerli bir IPv4 adresi olmalıdır', 'sosyallift-ai-pro'), $field),
            'ipv6'          => sprintf(__('%s geçerli bir IPv6 adresi olmalıdır', 'sosyallift-ai-pro'), $field),
        ];
        
        return $messages[$rule] ?? sprintf(__('%s doğrulama başarısız', 'sosyallift-ai-pro'), $field);
    }
    
    public function validate_csv_file(string $file_path, array $options = []): array {
        $options = wp_parse_args($options, [
            'max_size' => 10485760, // 10MB
            'allowed_mimes' => ['text/csv', 'text/plain', 'application/csv'],
            'required_columns' => [],
            'max_rows' => 10000,
        ]);
        
        $errors = [];
        $data = [];
        
        // Check file exists
        if (!file_exists($file_path)) {
            $errors[] = 'Dosya bulunamadı';
            return ['success' => false, 'errors' => $errors, 'data' => []];
        }
        
        // Check file size
        if (filesize($file_path) > $options['max_size']) {
            $errors[] = sprintf('Dosya boyutu %s MB limitini aşıyor', $options['max_size'] / 1048576);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        if (!in_array($mime, $options['allowed_mimes'])) {
            $errors[] = 'Geçersiz dosya tipi. Sadece CSV dosyaları kabul edilir';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'data' => []];
        }
        
        // Parse CSV
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            $errors[] = 'Dosya açılamadı';
            return ['success' => false, 'errors' => $errors, 'data' => []];
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $errors[] = 'CSV dosyası boş veya geçersiz';
            fclose($handle);
            return ['success' => false, 'errors' => $errors, 'data' => []];
        }
        
        // Validate required columns
        foreach ($options['required_columns'] as $column) {
            if (!in_array($column, $headers)) {
                $errors[] = sprintf('Gerekli sütun eksik: %s', $column);
            }
        }
        
        if (!empty($errors)) {
            fclose($handle);
            return ['success' => false, 'errors' => $errors, 'data' => []];
        }
        
        // Read data
        $row_count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $row_count++;
            
            if ($row_count > $options['max_rows']) {
                $errors[] = sprintf('Satır sayısı %s limitini aşıyor', $options['max_rows']);
                break;
            }
            
            $row_data = [];
            foreach ($headers as $index => $header) {
                $value = $row[$index] ?? '';
                $row_data[$header] = $this->sanitize_value($value);
            }
            
            $data[] = $row_data;
        }
        
        fclose($handle);
        
        if (empty($data)) {
            $errors[] = 'CSV dosyasında veri bulunamadı';
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $data,
            'stats' => [
                'rows' => $row_count,
                'columns' => count($headers),
                'headers' => $headers,
            ]
        ];
    }
}