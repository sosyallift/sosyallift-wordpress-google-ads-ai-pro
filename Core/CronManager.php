<?php
namespace SosyalliftAIPro\Core;

class CronManager {
    use \SosyalliftAIPro\Includes\Traits\Singleton;
    
    private $cron_intervals = [];
    private $scheduled_events = [];
    
    protected function __construct() {
        $this->init_default_intervals();
    }
    
    public function init(): void {
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
        $this->register_events();
    }
    
    private function init_default_intervals(): void {
        $this->cron_intervals = [
            'sl_ai_pro_5min' => [
                'interval' => 300,
                'display'  => __('Her 5 dakikada bir', 'sosyallift-ai-pro')
            ],
            'sl_ai_pro_15min' => [
                'interval' => 900,
                'display'  => __('Her 15 dakikada bir', 'sosyallift-ai-pro')
            ],
            'sl_ai_pro_30min' => [
                'interval' => 1800,
                'display'  => __('Her 30 dakikada bir', 'sosyallift-ai-pro')
            ],
            'sl_ai_pro_hourly' => [
                'interval' => 3600,
                'display'  => __('Saatlik', 'sosyallift-ai-pro')
            ],
            'sl_ai_pro_6hour' => [
                'interval' => 21600,
                'display'  => __('6 Saatte bir', 'sosyallift-ai-pro')
            ],
            'sl_ai_pro_12hour' => [
                'interval' => 43200,
                'display'  => __('12 Saatte bir', 'sosyallift-ai-pro')
            ],
        ];
    }
    
    public function add_cron_intervals(array $schedules): array {
        return array_merge($schedules, $this->cron_intervals);
    }
    
    private function register_events(): void {
        $this->scheduled_events = [
            'sl_ai_pro_cron_sync' => [
                'schedule' => 'sl_ai_pro_5min',
                'callback' => [$this, 'handle_sync'],
                'args'     => []
            ],
            'sl_ai_pro_cron_cleanup' => [
                'schedule' => 'daily',
                'callback' => [$this, 'handle_cleanup'],
                'args'     => []
            ],
            'sl_ai_pro_cron_reporting' => [
                'schedule' => 'daily',
                'callback' => [$this, 'handle_reporting'],
                'args'     => []
            ],
            'sl_ai_pro_cron_license_check' => [
                'schedule' => 'sl_ai_pro_12hour',
                'callback' => [$this, 'handle_license_check'],
                'args'     => []
            ],
        ];
        
        foreach ($this->scheduled_events as $event => $config) {
            if (!wp_next_scheduled($event)) {
                wp_schedule_event(time(), $config['schedule'], $event);
            }
        }
    }
    
    public function handle_sync(): void {
        Logger::info('Cron: Data sync started');
        
        try {
            // Module sync işlemleri
            $modules = apply_filters('sl_ai_pro/sync_modules', []);
            
            foreach ($modules as $module) {
                if (method_exists($module, 'cron_sync')) {
                    $module->cron_sync();
                }
            }
            
            Logger::info('Cron: Data sync completed');
            
        } catch (\Exception $e) {
            Logger::error('Cron: Data sync failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function handle_cleanup(): void {
        Logger::info('Cron: Cleanup started');
        
        try {
            // Eski logları temizle
            Logger::cleanup_old_logs(30);
            
            // Cache temizle
            $cache = CacheManager::get_instance();
            $cache->clean_expired();
            
            // Geçici dosyaları temizle
            $this->cleanup_temp_files();
            
            Logger::info('Cron: Cleanup completed');
            
        } catch (\Exception $e) {
            Logger::error('Cron: Cleanup failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function handle_reporting(): void {
        Logger::info('Cron: Reporting started');
        
        try {
            // Günlük raporları oluştur
            $this->generate_daily_reports();
            
            // Alert'leri kontrol et
            $this->check_alerts();
            
            // Email bildirimleri gönder
            $this->send_notifications();
            
            Logger::info('Cron: Reporting completed');
            
        } catch (\Exception $e) {
            Logger::error('Cron: Reporting failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function handle_license_check(): void {
        // Lisans kontrolü (eğer LicenseManager varsa)
        if (class_exists('SosyalliftAIPro\Core\LicenseManager')) {
            $license = LicenseManager::get_instance();
            $license->verify_license();
        }
    }
    
    private function cleanup_temp_files(): void {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sosyallift-ai-pro/temp/';
        
        if (!file_exists($temp_dir)) {
            return;
        }
        
        $files = glob($temp_dir . '*');
        $cutoff = time() - (24 * 60 * 60); // 24 saat
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    private function generate_daily_reports(): void {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Performans raporu
        $performance_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_keywords,
                SUM(monthly_searches) as total_searches,
                AVG(intent_score) as avg_intent_score,
                AVG(commercial_score) as avg_commercial_score
            FROM {$wpdb->prefix}sl_ai_keywords
            WHERE DATE(created_at) = %s",
            $yesterday
        ));
        
        // Alert raporu
        $alerts_data = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_alerts,
                SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_alerts,
                SUM(CASE WHEN severity = 'warning' THEN 1 ELSE 0 END) as warning_alerts
            FROM {$wpdb->prefix}sl_ai_alerts
            WHERE DATE(created_at) = %s",
            $yesterday
        ));
        
        // Raporu kaydet
        update_option('sl_ai_pro_daily_report_' . $yesterday, [
            'date' => $yesterday,
            'performance' => $performance_data,
            'alerts' => $alerts_data,
            'generated_at' => current_time('mysql')
        ]);
    }
    
    private function check_alerts(): void {
        global $wpdb;
        
        // Critical performance drop check
        $recent_performance = $wpdb->get_var(
            "SELECT AVG(total_score) 
             FROM {$wpdb->prefix}sl_ai_scores 
             WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        $previous_performance = $wpdb->get_var(
            "SELECT AVG(total_score) 
             FROM {$wpdb->prefix}sl_ai_scores 
             WHERE analyzed_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        if ($recent_performance && $previous_performance) {
            $drop_percentage = (($previous_performance - $recent_performance) / $previous_performance) * 100;
            
            if ($drop_percentage > 20) {
                $this->create_alert(
                    'performance_drop',
                    'critical',
                    sprintf(__('Performans %d%% düştü', 'sosyallift-ai-pro'), round($drop_percentage)),
                    __('Son 7 günde SEO performansında önemli düşüş tespit edildi.', 'sosyallift-ai-pro')
                );
            }
        }
    }
    
    private function create_alert(string $type, string $severity, string $title, string $message): void {
        global $wpdb;
        
        $wpdb->insert(
            "{$wpdb->prefix}sl_ai_alerts",
            [
                'alert_type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    private function send_notifications(): void {
        $notification_email = get_option('sl_ai_pro_notification_email', get_option('admin_email'));
        
        if (!$notification_email) {
            return;
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $report = get_option('sl_ai_pro_daily_report_' . $yesterday, []);
        
        if (empty($report)) {
            return;
        }
        
        $subject = sprintf(__('Günlük Rapor - %s', 'sosyallift-ai-pro'), $yesterday);
        $message = $this->generate_email_content($report);
        
        wp_mail($notification_email, $subject, $message, [
            'Content-Type: text/html; charset=UTF-8',
            'From: Sosyallift AI Pro <noreply@' . $_SERVER['HTTP_HOST'] . '>'
        ]);
    }
    
    private function generate_email_content(array $report): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Günlük Rapor</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .metric { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .metric h3 { margin-top: 0; color: #0073aa; }
                .alert { border-left: 4px solid #dc3232; padding-left: 15px; }
                .alert.critical { border-color: #dc3232; }
                .alert.warning { border-color: #ffb900; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Sosyallift AI Pro Günlük Rapor</h1>
                    <p><?php echo $report['date']; ?></p>
                </div>
                
                <div class="content">
                    <h2>Performans Özeti</h2>
                    
                    <?php if ($report['performance']): ?>
                    <div class="metric">
                        <h3>Anahtar Kelime Analizi</h3>
                        <p>Toplam Kelime: <?php echo $report['performance']->total_keywords; ?></p>
                        <p>Toplam Arama: <?php echo number_format($report['performance']->total_searches); ?></p>
                        <p>Ortalama Intent Skoru: <?php echo round($report['performance']->avg_intent_score, 2); ?></p>
                        <p>Ortalama Ticari Skor: <?php echo round($report['performance']->avg_commercial_score, 2); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($report['alerts']): ?>
                    <div class="metric">
                        <h3>Alert Durumu</h3>
                        <p>Toplam Alert: <?php echo $report['alerts']->total_alerts; ?></p>
                        <p>Kritik Alert: <?php echo $report['alerts']->critical_alerts; ?></p>
                        <p>Uyarı Alert: <?php echo $report['alerts']->warning_alerts; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                        Bu rapor otomatik olarak oluşturulmuştur.<br>
                        <a href="<?php echo admin_url('admin.php?page=sosyallift-ai-pro'); ?>">Dashboard'a Git</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function get_scheduled_events(): array {
        $events = [];
        
        foreach ($this->scheduled_events as $event => $config) {
            $events[$event] = [
                'next_run' => wp_next_scheduled($event),
                'schedule' => $config['schedule'],
                'is_scheduled' => wp_next_scheduled($event) !== false
            ];
        }
        
        return $events;
    }
    
    public function run_now(string $event): bool {
        if (!isset($this->scheduled_events[$event])) {
            return false;
        }
        
        $config = $this->scheduled_events[$event];
        
        do_action($event);
        
        return true;
    }
    
    public function clear_all(): void {
        foreach (array_keys($this->scheduled_events) as $event) {
            wp_clear_scheduled_hook($event);
        }
    }
}