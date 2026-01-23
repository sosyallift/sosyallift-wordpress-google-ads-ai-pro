<?php
namespace SosyalliftAIPro\Core;

class CacheManager {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $enabled = true;
    private $prefix = 'sl_ai_pro_';
    private $default_ttl = 300;
    
    protected function __construct() {
        $this->enabled = defined('SL_AI_PRO_CACHE_ENABLED') ? SL_AI_PRO_CACHE_ENABLED : true;
        $this->default_ttl = get_option('sl_ai_pro_cache_ttl', 300);
    }
    
    public function get(string $key, $default = null) {
        if (!$this->enabled) {
            return $default;
        }
        
        $full_key = $this->prefix . $key;
        $value = wp_cache_get($full_key, SL_AI_PRO_CACHE_GROUP);
        
        if (false === $value) {
            // Database cache fallback
            $value = get_transient($full_key);
            
            if (false === $value) {
                return $default;
            }
            
            // Store in object cache for next request
            wp_cache_set($full_key, $value, SL_AI_PRO_CACHE_GROUP, $this->default_ttl);
        }
        
        return $value;
    }
    
    public function set(string $key, $value, int $ttl = null): bool {
        if (!$this->enabled) {
            return false;
        }
        
        $full_key = $this->prefix . $key;
        $ttl = $ttl ?: $this->default_ttl;
        
        // Object cache
        wp_cache_set($full_key, $value, SL_AI_PRO_CACHE_GROUP, $ttl);
        
        // Database cache as fallback
        set_transient($full_key, $value, $ttl);
        
        return true;
    }
    
    public function delete(string $key): bool {
        $full_key = $this->prefix . $key;
        
        // Object cache
        wp_cache_delete($full_key, SL_AI_PRO_CACHE_GROUP);
        
        // Database cache
        delete_transient($full_key);
        
        return true;
    }
    
    public function delete_by_prefix(string $prefix): bool {
        global $wpdb;
        
        $pattern = $this->prefix . $prefix . '%';
        
        // Object cache (WordPress doesn't have built-in pattern delete)
        // We'll rely on database cache for pattern deletion
        
        // Database cache
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $pattern,
                '_transient_timeout_' . $pattern
            )
        );
        
        return true;
    }
    
    public function increment(string $key, int $offset = 1): int {
        $value = $this->get($key, 0);
        $new_value = $value + $offset;
        $this->set($key, $new_value);
        
        return $new_value;
    }
    
    public function decrement(string $key, int $offset = 1): int {
        $value = $this->get($key, 0);
        $new_value = max(0, $value - $offset);
        $this->set($key, $new_value);
        
        return $new_value;
    }
    
    public function flush(): bool {
        // Object cache
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(SL_AI_PRO_CACHE_GROUP);
        }
        
        // Database cache
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->prefix . '%',
                '_transient_timeout_' . $this->prefix . '%'
            )
        );
        
        return true;
    }
    
    public function clean_expired(): int {
        global $wpdb;
        
        $now = time();
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE t, tt FROM {$wpdb->options} t
                INNER JOIN {$wpdb->options} tt ON tt.option_name = CONCAT('_transient_timeout_', t.option_name)
                WHERE t.option_name LIKE %s AND tt.option_value < %d",
                '_transient_' . $this->prefix . '%',
                $now
            )
        );
        
        return (int) $deleted;
    }
    
    public function get_stats(): array {
        global $wpdb;
        
        $stats = [
            'enabled' => $this->enabled,
            'prefix' => $this->prefix,
            'default_ttl' => $this->default_ttl,
            'total_items' => 0,
            'expired_items' => 0,
            'memory_usage' => 0,
        ];
        
        // Get total cache items
        $stats['total_items'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->prefix . '%'
            )
        );
        
        // Get expired items
        $now = time();
        $stats['expired_items'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} t
                INNER JOIN {$wpdb->options} tt ON tt.option_name = CONCAT('_transient_timeout_', t.option_name)
                WHERE t.option_name LIKE %s AND tt.option_value < %d",
                '_transient_' . $this->prefix . '%',
                $now
            )
        );
        
        // Estimate memory usage
        $cache_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->prefix . '%'
            )
        );
        
        foreach ($cache_items as $item) {
            $stats['memory_usage'] += strlen(maybe_serialize($item->option_value));
        }
        
        $stats['memory_usage'] = size_format($stats['memory_usage']);
        
        return $stats;
    }
}