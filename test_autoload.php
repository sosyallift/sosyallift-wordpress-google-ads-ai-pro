<?php
// Test autoloader
echo "Testing autoloader...\n";
// Test edilecek sınıflar
$test_classes = [
    'SosyalliftAIPro\Core\Bootstrap',
    'SosyalliftAIPro\Core\Requirements',
    'SosyalliftAIPro\Core\Security',
    'SosyalliftAIPro\Includes\Traits\Singleton'
];
foreach ($test_classes as $class) {
    if (class_exists($class)) {
        echo "✓ $class loaded successfully\n";
    } else {
        echo "✗ $class NOT FOUND\n";
    }
}
echo "\nAutoload test completed.\n";
