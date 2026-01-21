<?php
// Bu dosya Bootstrap.php içinde tanımlanacak, ayrı dosya olarak ekliyorum
// Eğer tanımlanmadıysa tanımla
if (!defined('SL_AI_PRO_CACHE_ENABLED')) {
    define('SL_AI_PRO_CACHE_ENABLED', true);
}

if (!defined('SL_AI_PRO_API_TIMEOUT')) {
    define('SL_AI_PRO_API_TIMEOUT', 30);
}

if (!defined('SL_AI_PRO_MAX_LOG_SIZE')) {
    define('SL_AI_PRO_MAX_LOG_SIZE', 10485760); // 10MB
}

if (!defined('SL_AI_PRO_DEBUG_MODE')) {
    define('SL_AI_PRO_DEBUG_MODE', WP_DEBUG);
}