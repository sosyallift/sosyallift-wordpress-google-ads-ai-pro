<?php
/**
 * Plugin Name: Sosyallift AI Pro
 * Plugin URI: https://sosyallift.com
 * Description: AI-Powered SEO & Ads Intelligence Platform
 * Version: 2.0.0
 * Author: Sosyallift
 * License: GPL v3
 */
if (!defined('ABSPATH')) exit;
// Temel sabitler
define('SL_AI_PATH', plugin_dir_path(__FILE__));
define('SL_AI_URL', plugin_dir_url(__FILE__));
define('SL_AI_VERSION', '2.0.0');
// Bootstrap dosyasını yükle
require_once SL_AI_PATH . 'core/Bootstrap.php';
// Plugin başlat
SL_AI\Core\Bootstrap::init();
