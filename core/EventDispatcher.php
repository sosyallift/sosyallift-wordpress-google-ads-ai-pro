<?php
namespace SosyalliftAIPro\Core;

class EventDispatcher {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $listeners = [];
    private $wildcards = [];
    
    public function listen(string $event, callable $listener, int $priority = 10): void {
        $this->listeners[$event][$priority][] = $listener;
        
        // Priority'a göre sırala
        ksort($this->listeners[$event]);
    }
    
    public function listen_pattern(string $pattern, callable $listener): void {
        $this->wildcards[$pattern][] = $listener;
    }
    
    public function dispatch(string $event, array $args = []): void {
        // Exact match listeners
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $priority => $listeners) {
                foreach ($listeners as $listener) {
                    call_user_func_array($listener, $args);
                }
            }
        }
        
        // Wildcard listeners
        foreach ($this->wildcards as $pattern => $listeners) {
            if (fnmatch($pattern, $event)) {
                foreach ($listeners as $listener) {
                    call_user_func_array($listener, $args);
                }
            }
        }
    }
    
    public function remove(string $event, callable $listener_to_remove = null): void {
        if (!isset($this->listeners[$event])) {
            return;
        }
        
        if ($listener_to_remove === null) {
            unset($this->listeners[$event]);
            return;
        }
        
        foreach ($this->listeners[$event] as $priority => &$listeners) {
            foreach ($listeners as $key => $listener) {
                if ($listener === $listener_to_remove) {
                    unset($listeners[$key]);
                }
            }
            
            // Boş priority'leri temizle
            if (empty($listeners)) {
                unset($this->listeners[$event][$priority]);
            }
        }
    }
    
    public function get_listeners(string $event = null): array {
        if ($event === null) {
            return $this->listeners;
        }
        
        return $this->listeners[$event] ?? [];
    }
    
    public function has_listeners(string $event): bool {
        if (isset($this->listeners[$event]) && !empty($this->listeners[$event])) {
            return true;
        }
        
        // Wildcard kontrolü
        foreach (array_keys($this->wildcards) as $pattern) {
            if (fnmatch($pattern, $event)) {
                return true;
            }
        }
        
        return false;
    }
}
