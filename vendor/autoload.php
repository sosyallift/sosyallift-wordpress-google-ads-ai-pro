<?php
error_log("vendor/autoload.php çalıştı. __DIR__ = " . __DIR__);
spl_autoload_register(function ($class) {
    $prefix = 'SosyalliftAIPro\\';
    
    // Eklentinin kök dizini
    $base_dir = __DIR__ . '/../';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    error_log("Aranan sınıf: $class");
    error_log("Dosya yolu: $file");
    error_log("Dosya var mı: " . (file_exists($file) ? 'EVET' : 'HAYIR'));
    
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("Dosya BULUNAMADI: $file");
    }
});