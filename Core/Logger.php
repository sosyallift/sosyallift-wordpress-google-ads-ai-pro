<?php
namespace SosyalliftAIPro\Core;

class Logger {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $log_dir;
    private $log_level;
    private $max_file_size = 10485760; // 10MB
    private $log_queue = [];
    
    protected function __construct() {
        $this->log_dir = SL_AI_PRO_PATH . 'logs/';
        $this->log_level = defined('SL_AI_PRO_LOG_LEVEL') ? SL_AI_PRO_LOG_LEVEL : 'error';
        
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        
        // .htaccess ile koruma
        if (!file_exists($this->log_dir . '.htaccess')) {
            file_put_contents($this->log_dir . '.htaccess', 'Deny from all');
        }
        
        // Shutdown hook for queued logs
        register_shutdown_function([$this, 'flush']);
    }
    
    public function log(string $level, string $message, array $context = []): void {
        if (!$this->should_log($level)) {
            return;
        }
        
        $log_entry = $this->format_entry($level, $message, $context);
        $this->log_queue[] = $log_entry;
        
        // Buffer dolduysa yaz
        if (count($this->log_queue) >= 10) {
            $this->write_queue();
        }
    }
    
    public static function debug(string $message, array $context = []): void {
        self::get_instance()->log('debug', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void {
        self::get_instance()->log('info', $message, $context);
    }
    
    public static function notice(string $message, array $context = []): void {
        self::get_instance()->log('notice', $message, $context);
    }
    
    public static function warning(string $message, array $context = []): void {
        self::get_instance()->log('warning', $message, $context);
    }
    
    public static function error(string $message, array $context = []): void {
        self::get_instance()->log('error', $message, $context);
    }
    
    public static function critical(string $message, array $context = []): void {
        self::get_instance()->log('critical', $message, $context);
    }
    
    public static function alert(string $message, array $context = []): void {
        self::get_instance()->log('alert', $message, $context);
    }
    
    public static function emergency(string $message, array $context = []): void {
        self::get_instance()->log('emergency', $message, $context);
    }
    
    public static function audit(string $action, array $context = []): void {
        $context['audit'] = true;
        $context['user_id'] = get_current_user_id();
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        self::get_instance()->log('info', "Audit: {$action}", $context);
    }
    
    public function flush(): void {
        if (!empty($this->log_queue)) {
            $this->write_queue();
        }
    }
    
    private function should_log(string $level): bool {
        $levels = [
            'debug' => 100,
            'info' => 200,
            'notice' => 250,
            'warning' => 300,
            'error' => 400,
            'critical' => 500,
            'alert' => 550,
            'emergency' => 600,
        ];
        
        $current_level = $levels[$this->log_level] ?? 400;
        $message_level = $levels[$level] ?? 400;
        
        return $message_level >= $current_level;
    }
    
    private function format_entry(string $level, string $message, array $context): string {
        $timestamp = current_time('mysql');
        $level_upper = strtoupper($level);
        
        $entry = "[{$timestamp}] {$level_upper}: {$message}";
        
        if (!empty($context)) {
            $context_str = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $entry .= " | Context: {$context_str}";
        }
        
        $entry .= PHP_EOL;
        
        return $entry;
    }
    
    private function write_queue(): void {
        if (empty($this->log_queue)) {
            return;
        }
        
        $log_file = $this->get_log_file();
        
        // Log rotation
        if (file_exists($log_file) && filesize($log_file) > $this->max_file_size) {
            $this->rotate_log($log_file);
        }
        
        $content = implode('', $this->log_queue);
        file_put_contents($log_file, $content, FILE_APPEND | LOCK_EX);
        
        $this->log_queue = [];
    }
    
    private function get_log_file(): string {
        $date = current_time('Y-m-d');
        return $this->log_dir . "sosyallift-{$date}.log";
    }
    
    private function rotate_log(string $current_file): void {
        $basename = basename($current_file, '.log');
        $timestamp = time();
        $new_file = $this->log_dir . "{$basename}-{$timestamp}.log";
        
        rename($current_file, $new_file);
        
        // Compress old logs
        $this->compress_old_logs();
    }
    
    private function compress_old_logs(): void {
        $files = glob($this->log_dir . '*.log');
        $now = time();
        $max_age = 30 * 24 * 60 * 60; // 30 days
        
        foreach ($files as $file) {
            if (filemtime($file) < ($now - $max_age)) {
                $compressed_file = $file . '.gz';
                
                if (function_exists('gzopen')) {
                    $data = file_get_contents($file);
                    $gz = gzopen($compressed_file, 'w9');
                    gzwrite($gz, $data);
                    gzclose($gz);
                    
                    unlink($file);
                }
            }
        }
    }
    
    public static function cleanup_old_logs(int $days = 30): void {
        $instance = self::get_instance();
        $files = glob($instance->log_dir . '*.log*');
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    public function get_logs(int $limit = 100, string $level = null, string $search = null): array {
        $files = glob($this->log_dir . '*.log');
        rsort($files);
        
        $logs = [];
        $count = 0;
        
        foreach ($files as $file) {
            if ($count >= $limit) {
                break;
            }
            
            $content = file_get_contents($file);
            $lines = array_reverse(explode(PHP_EOL, $content));
            
            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }
                
                if ($count >= $limit) {
                    break 2;
                }
                
                $log_entry = $this->parse_log_line($line);
                
                if (!$log_entry) {
                    continue;
                }
                
                // Level filter
                if ($level && $log_entry['level'] !== $level) {
                    continue;
                }
                
                // Search filter
                if ($search && stripos($log_entry['message'], $search) === false) {
                    continue;
                }
                
                $logs[] = $log_entry;
                $count++;
            }
        }
        
        return $logs;
    }
    
    private function parse_log_line(string $line): ?array {
        // Format: [timestamp] LEVEL: message | Context: json
        $pattern = '/^\[(.+?)\] (\w+): (.+?)(?:\s*\|\s*Context:\s*(.+))?$/';
        
        if (!preg_match($pattern, $line, $matches)) {
            return null;
        }
        
        $entry = [
            'timestamp' => $matches[1],
            'level' => strtolower($matches[2]),
            'message' => $matches[3],
            'context' => []
        ];
        
        if (!empty($matches[4])) {
            $context = json_decode($matches[4], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $entry['context'] = $context;
            }
        }
        
        return $entry;
    }
    
    public function get_stats(): array {
        $files = glob($this->log_dir . '*.log*');
        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'by_level' => [],
            'by_date' => []
        ];
        
        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);
            
            $filename = basename($file);
            if (preg_match('/sosyallift-(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
                $date = $matches[1];
                $stats['by_date'][$date] = ($stats['by_date'][$date] ?? 0) + filesize($file);
            }
        }
        
        // Analyze recent log file
        $recent_file = $this->get_log_file();
        if (file_exists($recent_file)) {
            $content = file_get_contents($recent_file);
            $lines = explode(PHP_EOL, $content);
            
            foreach ($lines as $line) {
                if (empty($line)) {
                    continue;
                }
                
                if (preg_match('/\] (\w+):/', $line, $matches)) {
                    $level = strtolower($matches[1]);
                    $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                }
            }
        }
        
        $stats['total_size'] = size_format($stats['total_size']);
        
        return $stats;
    }
}