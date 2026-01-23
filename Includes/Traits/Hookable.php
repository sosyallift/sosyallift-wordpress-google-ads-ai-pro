<?php
namespace SosyalliftAIPro\Includes\Traits;

trait Hookable {
    protected $hooks = [];
    
    public function add_action(string $hook, callable $callback, int $priority = 10, int $args = 1): void {
        add_action($hook, $callback, $priority, $args);
        $this->hooks['actions'][] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args
        ];
    }
    
    public function add_filter(string $hook, callable $callback, int $priority = 10, int $args = 1): void {
        add_filter($hook, $callback, $priority, $args);
        $this->hooks['filters'][] = [
            'hook' => $hook,
            'callback' => $callback,
            'priority' => $priority,
            'args' => $args
        ];
    }
    
    public function add_ajax(string $action, callable $callback, bool $nopriv = false): void {
        add_action('wp_ajax_' . $action, $callback);
        
        if ($nopriv) {
            add_action('wp_ajax_nopriv_' . $action, $callback);
        }
        
        $this->hooks['ajax'][] = [
            'action' => $action,
            'callback' => $callback,
            'nopriv' => $nopriv
        ];
    }
    
    public function add_shortcode(string $tag, callable $callback): void {
        add_shortcode($tag, $callback);
        $this->hooks['shortcodes'][] = [
            'tag' => $tag,
            'callback' => $callback
        ];
    }
    
    public function remove_all_hooks(): void {
        if (!empty($this->hooks['actions'])) {
            foreach ($this->hooks['actions'] as $hook) {
                remove_action($hook['hook'], $hook['callback'], $hook['priority']);
            }
        }
        
        if (!empty($this->hooks['filters'])) {
            foreach ($this->hooks['filters'] as $hook) {
                remove_filter($hook['hook'], $hook['callback'], $hook['priority']);
            }
        }
        
        $this->hooks = [];
    }
}