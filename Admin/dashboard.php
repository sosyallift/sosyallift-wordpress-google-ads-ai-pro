<?php
namespace SosyalliftAIPro\Admin;

use SosyalliftAIPro\Core\Security;
use SosyalliftAIPro\Core\CacheManager;
use SosyalliftAIPro\Modules\GoogleAds\ApiHandler;
use SosyalliftAIPro\Modules\SEO\RankMonitor;
use SosyalliftAIPro\Modules\Intent\IntentDetector;

class Dashboard {
    private $page_hook = '';
    private $security;
    private $cache;
    
    public function __construct() {
        $this->security = Security::get_instance();
        $this->cache = CacheManager::get_instance();
        
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_sl_ai_pro_refresh_dashboard', [$this, 'ajax_refresh_dashboard']);
        add_action('wp_ajax_sl_ai_pro_export_dashboard', [$this, 'ajax_export_dashboard']);
        add_action('wp_ajax_sl_ai_pro_get_widget_data', [$this, 'ajax_get_widget_data']);
    }
    
    public function register_menu(): void {
        $this->page_hook = add_menu_page(
            __('AI Intelligence Dashboard', 'sosyallift-ai-pro'),
            __('AI Intelligence', 'sosyallift-ai-pro'),
            'manage_options',
            'sl-ai-pro-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-chart-line',
            30
        );
        
        // Add submenu for quick access
        add_submenu_page(
            'sl-ai-pro-dashboard',
            __('Performance Analytics', 'sosyallift-ai-pro'),
            __('Performance', 'sosyallift-ai-pro'),
            'manage_options',
            'sl-ai-pro-dashboard#performance',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'sl-ai-pro-dashboard',
            __('Keyword Intelligence', 'sosyallift-ai-pro'),
            __('Keywords', 'sosyallift-ai-pro'),
            'manage_options',
            'sl-ai-pro-dashboard#keywords',
            [$this, 'render_dashboard']
        );
        
        add_submenu_page(
            'sl-ai-pro-dashboard',
            __('Intent Analysis', 'sosyallift-ai-pro'),
            __('Intent', 'sosyallift-ai-pro'),
            'manage_options',
            'sl-ai-pro-dashboard#intent',
            [$this, 'render_dashboard']
        );
        
        // Add screen options
        add_action("load-{$this->page_hook}", [$this, 'add_screen_options']);
    }
    
    public function add_screen_options(): void {
        $screen = get_current_screen();
        
        if ($screen->id !== $this->page_hook) {
            return;
        }
        
        add_screen_option('layout_columns', [
            'max'       => 4,
            'default'   => 2,
        ]);
        
        // Add help tabs
        $screen->add_help_tab([
            'id'        => 'sl-ai-pro-dashboard-overview',
            'title'     => __('Overview', 'sosyallift-ai-pro'),
            'content'   => $this->get_help_content('overview'),
        ]);
        
        $screen->add_help_tab([
            'id'        => 'sl-ai-pro-dashboard-metrics',
            'title'     => __('Metrics Explained', 'sosyallift-ai-pro'),
            'content'   => $this->get_help_content('metrics'),
        ]);
        
        $screen->set_help_sidebar($this->get_help_sidebar());
    }
    
    public function enqueue_assets(string $hook): void {
        if ($hook !== $this->page_hook) {
            return;
        }
        
        // Enqueue core WordPress scripts
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-resizable');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue vendor scripts
        wp_enqueue_script(
            'apexcharts',
            SL_AI_PRO_URL . 'assets/js/vendor/apexcharts.min.js',
            [],
            '3.35.0',
            true
        );
        
        wp_enqueue_script(
            'datatables',
            SL_AI_PRO_URL . 'assets/js/vendor/datatables.min.js',
            ['jquery'],
            '1.13.4',
            true
        );
        
        wp_enqueue_script(
            'select2',
            SL_AI_PRO_URL . 'assets/js/vendor/select2.min.js',
            ['jquery'],
            '4.1.0',
            true
        );
        
        // Enqueue plugin scripts
        wp_enqueue_script(
            'sl-ai-pro-dashboard',
            SL_AI_PRO_URL . 'assets/js/admin/dashboard.js',
            ['jquery', 'apexcharts', 'datatables', 'select2', 'wp-color-picker'],
            SL_AI_PRO_VERSION,
            true
        );
        
        // Enqueue styles
        wp_enqueue_style(
            'sl-ai-pro-admin',
            SL_AI_PRO_URL . 'assets/css/admin/main.css',
            [],
            SL_AI_PRO_VERSION
        );
        
        wp_enqueue_style(
            'apexcharts',
            SL_AI_PRO_URL . 'assets/css/vendor/apexcharts.css',
            [],
            '3.35.0'
        );
        
        wp_enqueue_style(
            'datatables',
            SL_AI_PRO_URL . 'assets/css/vendor/datatables.css',
            [],
            '1.13.4'
        );
        
        wp_enqueue_style(
            'select2',
            SL_AI_PRO_URL . 'assets/css/vendor/select2.css',
            [],
            '4.1.0'
        );
        
        // Localize script with data
        wp_localize_script('sl-ai-pro-dashboard', 'sl_ai_pro_dashboard', [
            'ajax_url'          => admin_url('admin-ajax.php'),
            'nonce'             => $this->security->create_nonce('ajax'),
            'current_page'      => $this->get_current_tab(),
            'date_range'        => $this->get_date_range(),
            'refresh_interval'  => $this->get_refresh_interval(),
            'timezone'          => wp_timezone_string(),
            'currency'          => get_option('sl_ai_pro_currency', 'TRY'),
            'i18n'              => $this->get_i18n_strings(),
            'user_preferences'  => $this->get_user_preferences(),
            'widgets'           => $this->get_widget_config(),
        ]);
        
        // Add inline styles for customizations
        $this->add_custom_styles();
    }
    
    public function render_dashboard(): void {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'sosyallift-ai-pro'));
        }
        
        // Check for setup completion
        if (!$this->is_setup_complete()) {
            $this->render_setup_wizard();
            return;
        }
        
        ?>
        <div class="wrap sl-ai-pro-dashboard">
            <header class="sl-ai-pro-dashboard-header">
                <div class="header-left">
                    <h1 class="wp-heading-inline">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('AI Intelligence Dashboard', 'sosyallift-ai-pro'); ?>
                    </h1>
                    <span class="sl-ai-pro-version">v<?php echo SL_AI_PRO_VERSION; ?></span>
                </div>
                
                <div class="header-right">
                    <div class="header-actions">
                        <button class="button button-secondary" id="sl-ai-pro-refresh">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'sosyallift-ai-pro'); ?>
                        </button>
                        
                        <button class="button button-secondary" id="sl-ai-pro-export">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export', 'sosyallift-ai-pro'); ?>
                        </button>
                        
                        <div class="date-range-selector">
                            <select id="sl-ai-pro-date-range">
                                <option value="today"><?php _e('Today', 'sosyallift-ai-pro'); ?></option>
                                <option value="yesterday"><?php _e('Yesterday', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_7_days" selected><?php _e('Last 7 Days', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_30_days"><?php _e('Last 30 Days', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_90_days"><?php _e('Last 90 Days', 'sosyallift-ai-pro'); ?></option>
                                <option value="this_month"><?php _e('This Month', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_month"><?php _e('Last Month', 'sosyallift-ai-pro'); ?></option>
                                <option value="custom"><?php _e('Custom Range', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="custom-date-range" style="display: none;">
                            <input type="date" id="sl-ai-pro-date-from" class="small-text">
                            <span>to</span>
                            <input type="date" id="sl-ai-pro-date-to" class="small-text">
                            <button class="button button-small" id="sl-ai-pro-apply-custom">Apply</button>
                        </div>
                    </div>
                </div>
            </header>
            
            <?php $this->render_quick_stats(); ?>
            
            <div class="sl-ai-pro-dashboard-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#overview" class="nav-tab nav-tab-active">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('Overview', 'sosyallift-ai-pro'); ?>
                    </a>
                    <a href="#performance" class="nav-tab">
                        <span class="dashicons dashicons-performance"></span>
                        <?php _e('Performance', 'sosyallift-ai-pro'); ?>
                    </a>
                    <a href="#keywords" class="nav-tab">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Keywords', 'sosyallift-ai-pro'); ?>
                    </a>
                    <a href="#intent" class="nav-tab">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php _e('Intent', 'sosyallift-ai-pro'); ?>
                    </a>
                    <a href="#recommendations" class="nav-tab">
                        <span class="dashicons dashicons-thumbs-up"></span>
                        <?php _e('Recommendations', 'sosyallift-ai-pro'); ?>
                    </a>
                    <a href="#alerts" class="nav-tab">
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Alerts', 'sosyallift-ai-pro'); ?>
                        <span class="alert-count badge">0</span>
                    </a>
                </nav>
            </div>
            
            <div class="sl-ai-pro-dashboard-content">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <div class="row">
                        <div class="col-8">
                            <?php $this->render_overview_charts(); ?>
                        </div>
                        <div class="col-4">
                            <?php $this->render_overview_sidebar(); ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <?php $this->render_top_keywords(); ?>
                        </div>
                        <div class="col-6">
                            <?php $this->render_top_pages(); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Tab -->
                <div id="performance" class="tab-content">
                    <?php $this->render_performance_tab(); ?>
                </div>
                
                <!-- Keywords Tab -->
                <div id="keywords" class="tab-content">
                    <?php $this->render_keywords_tab(); ?>
                </div>
                
                <!-- Intent Tab -->
                <div id="intent" class="tab-content">
                    <?php $this->render_intent_tab(); ?>
                </div>
                
                <!-- Recommendations Tab -->
                <div id="recommendations" class="tab-content">
                    <?php $this->render_recommendations_tab(); ?>
                </div>
                
                <!-- Alerts Tab -->
                <div id="alerts" class="tab-content">
                    <?php $this->render_alerts_tab(); ?>
                </div>
            </div>
            
            <!-- Dashboard Modals -->
            <?php $this->render_modals(); ?>
            
            <!-- Loading Overlay -->
            <div id="sl-ai-pro-loading" class="loading-overlay">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="loading-text"><?php _e('Loading dashboard data...', 'sosyallift-ai-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_quick_stats(): void {
        $stats = $this->get_quick_stats();
        ?>
        <div class="sl-ai-pro-quick-stats">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-megaphone"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['ads']['clicks']); ?></h3>
                        <p><?php _e('Ad Clicks', 'sosyallift-ai-pro'); ?></p>
                        <div class="stat-change <?php echo $stats['ads']['change']['class']; ?>">
                            <span class="dashicons dashicons-<?php echo $stats['ads']['change']['icon']; ?>"></span>
                            <?php echo $stats['ads']['change']['value']; ?>%
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['seo']['clicks']); ?></h3>
                        <p><?php _e('Organic Clicks', 'sosyallift-ai-pro'); ?></p>
                        <div class="stat-change <?php echo $stats['seo']['change']['class']; ?>">
                            <span class="dashicons dashicons-<?php echo $stats['seo']['change']['icon']; ?>"></span>
                            <?php echo $stats['seo']['change']['value']; ?>%
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['conversions']['count'], 0); ?></h3>
                        <p><?php _e('Conversions', 'sosyallift-ai-pro'); ?></p>
                        <div class="stat-change <?php echo $stats['conversions']['change']['class']; ?>">
                            <span class="dashicons dashicons-<?php echo $stats['conversions']['change']['icon']; ?>"></span>
                            <?php echo $stats['conversions']['change']['value']; ?>%
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-bar"></span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['intent']['commercial'], 1); ?>%</h3>
                        <p><?php _e('Commercial Intent', 'sosyallift-ai-pro'); ?></p>
                        <div class="stat-change <?php echo $stats['intent']['change']['class']; ?>">
                            <span class="dashicons dashicons-<?php echo $stats['intent']['change']['icon']; ?>"></span>
                            <?php echo $stats['intent']['change']['value']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_quick_stats(): array {
        $cache_key = 'quick_stats_' . get_current_user_id() . '_' . $this->get_date_range();
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        global $wpdb;
        $range = $this->get_date_range_sql();
        
        $stats = [
            'ads' => [
                'clicks'    => 0,
                'cost'      => 0,
                'change'    => ['value' => 0, 'icon' => 'minus', 'class' => 'neutral'],
            ],
            'seo' => [
                'clicks'    => 0,
                'impressions' => 0,
                'change'    => ['value' => 0, 'icon' => 'minus', 'class' => 'neutral'],
            ],
            'conversions' => [
                'count'     => 0,
                'value'     => 0,
                'change'    => ['value' => 0, 'icon' => 'minus', 'class' => 'neutral'],
            ],
            'intent' => [
                'commercial'    => 0,
                'informational' => 0,
                'change'        => ['value' => 0, 'icon' => 'minus', 'class' => 'neutral'],
            ],
        ];
        
        // Get Ads stats
        $ads_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(clicks) as clicks,
                SUM(cost) as cost
            FROM {$wpdb->prefix}sl_ai_logs 
            WHERE source = 'ads' 
            AND created_at >= %s
        ", $range['start']));
        
        if ($ads_stats) {
            $stats['ads']['clicks'] = (int) $ads_stats->clicks;
            $stats['ads']['cost'] = (float) $ads_stats->cost;
        }
        
        // Get SEO stats
        $seo_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                SUM(clicks) as clicks,
                SUM(impressions) as impressions
            FROM {$wpdb->prefix}sl_ai_seo_data 
            WHERE date >= %s
        ", $range['start']));
        
        if ($seo_stats) {
            $stats['seo']['clicks'] = (int) $seo_stats->clicks;
            $stats['seo']['impressions'] = (int) $seo_stats->impressions;
        }
        
        // Get Conversions
        $conv_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as count,
                SUM(value) as value
            FROM {$wpdb->prefix}sl_ai_conversions 
            WHERE created_at >= %s
        ", $range['start']));
        
        if ($conv_stats) {
            $stats['conversions']['count'] = (int) $conv_stats->count;
            $stats['conversions']['value'] = (float) $conv_stats->value;
        }
        
        // Get Intent stats
        $intent_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                AVG(CASE WHEN intent_type = 'commercial' THEN 1 ELSE 0 END) * 100 as commercial_pct
            FROM {$wpdb->prefix}sl_ai_intent 
            WHERE analyzed_at >= %s
        ", $range['start']));
        
        if ($intent_stats) {
            $stats['intent']['commercial'] = (float) $intent_stats->commercial_pct;
        }
        
        // Calculate changes vs previous period
        $previous_stats = $this->get_previous_period_stats($range);
        $stats = $this->calculate_changes($stats, $previous_stats);
        
        // Cache for 5 minutes
        $this->cache->set($cache_key, $stats, 300);
        
        return $stats;
    }
    
    private function get_date_range_sql(): array {
        $range = $this->get_date_range();
        $now = current_time('mysql');
        
        switch ($range) {
            case 'today':
                $start = date('Y-m-d 00:00:00');
                break;
            case 'yesterday':
                $start = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $end = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case 'last_7_days':
                $start = date('Y-m-d 00:00:00', strtotime('-6 days'));
                break;
            case 'last_30_days':
                $start = date('Y-m-d 00:00:00', strtotime('-29 days'));
                break;
            case 'last_90_days':
                $start = date('Y-m-d 00:00:00', strtotime('-89 days'));
                break;
            case 'this_month':
                $start = date('Y-m-01 00:00:00');
                break;
            case 'last_month':
                $start = date('Y-m-01 00:00:00', strtotime('-1 month'));
                $end = date('Y-m-t 23:59:59', strtotime('-1 month'));
                break;
            default:
                $start = date('Y-m-d 00:00:00', strtotime('-6 days'));
                break;
        }
        
        if (!isset($end)) {
            $end = $now;
        }
        
        return ['start' => $start, 'end' => $end];
    }
    
    private function get_previous_period_stats(array $current_range): array {
        $duration = strtotime($current_range['end']) - strtotime($current_range['start']);
        $previous_start = date('Y-m-d H:i:s', strtotime($current_range['start']) - $duration - 1);
        $previous_end = date('Y-m-d H:i:s', strtotime($current_range['start']) - 1);
        
        global $wpdb;
        
        $stats = [
            'ads_clicks'        => 0,
            'seo_clicks'        => 0,
            'conversions_count' => 0,
            'intent_commercial' => 0,
        ];
        
        // Get previous period stats
        $ads_stats = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(clicks) 
            FROM {$wpdb->prefix}sl_ai_logs 
            WHERE source = 'ads' 
            AND created_at BETWEEN %s AND %s
        ", $previous_start, $previous_end));
        
        $stats['ads_clicks'] = (int) $ads_stats;
        
        $seo_stats = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(clicks) 
            FROM {$wpdb->prefix}sl_ai_seo_data 
            WHERE date BETWEEN %s AND %s
        ", $previous_start, $previous_end));
        
        $stats['seo_clicks'] = (int) $seo_stats;
        
        $conv_stats = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}sl_ai_conversions 
            WHERE created_at BETWEEN %s AND %s
        ", $previous_start, $previous_end));
        
        $stats['conversions_count'] = (int) $conv_stats;
        
        $intent_stats = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(CASE WHEN intent_type = 'commercial' THEN 1 ELSE 0 END) * 100 
            FROM {$wpdb->prefix}sl_ai_intent 
            WHERE analyzed_at BETWEEN %s AND %s
        ", $previous_start, $previous_end));
        
        $stats['intent_commercial'] = (float) $intent_stats;
        
        return $stats;
    }
    
    private function calculate_changes(array $current, array $previous): array {
        // Ads clicks change
        if ($previous['ads_clicks'] > 0) {
            $change = (($current['ads']['clicks'] - $previous['ads_clicks']) / $previous['ads_clicks']) * 100;
            $current['ads']['change'] = $this->format_change($change);
        }
        
        // SEO clicks change
        if ($previous['seo_clicks'] > 0) {
            $change = (($current['seo']['clicks'] - $previous['seo_clicks']) / $previous['seo_clicks']) * 100;
            $current['seo']['change'] = $this->format_change($change);
        }
        
        // Conversions change
        if ($previous['conversions_count'] > 0) {
            $change = (($current['conversions']['count'] - $previous['conversions_count']) / $previous['conversions_count']) * 100;
            $current['conversions']['change'] = $this->format_change($change);
        }
        
        // Intent change
        if ($previous['intent_commercial'] > 0) {
            $change = $current['intent']['commercial'] - $previous['intent_commercial'];
            $current['intent']['change'] = $this->format_change($change);
        }
        
        return $current;
    }
    
    private function format_change(float $change): array {
        $change = round($change, 1);
        
        if ($change > 0) {
            return [
                'value' => abs($change),
                'icon'  => 'arrow-up-alt',
                'class' => 'positive',
            ];
        } elseif ($change < 0) {
            return [
                'value' => abs($change),
                'icon'  => 'arrow-down-alt',
                'class' => 'negative',
            ];
        } else {
            return [
                'value' => 0,
                'icon'  => 'minus',
                'class' => 'neutral',
            ];
        }
    }
    
    private function render_overview_charts(): void {
        ?>
        <div class="sl-ai-pro-chart-container">
            <div class="chart-header">
                <h3><?php _e('Performance Overview', 'sosyallift-ai-pro'); ?></h3>
                <div class="chart-controls">
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-color" style="background-color: #3b82f6;"></span>
                            <?php _e('Ads', 'sosyallift-ai-pro'); ?>
                        </span>
                        <span class="legend-item">
                            <span class="legend-color" style="background-color: #10b981;"></span>
                            <?php _e('SEO', 'sosyallift-ai-pro'); ?>
                        </span>
                        <span class="legend-item">
                            <span class="legend-color" style="background-color: #f59e0b;"></span>
                            <?php _e('Intent', 'sosyallift-ai-pro'); ?>
                        </span>
                    </div>
                    <div class="chart-types">
                        <button class="button button-small chart-type-btn active" data-type="line">
                            <span class="dashicons dashicons-chart-line"></span>
                        </button>
                        <button class="button button-small chart-type-btn" data-type="bar">
                            <span class="dashicons dashicons-chart-bar"></span>
                        </button>
                        <button class="button button-small chart-type-btn" data-type="area">
                            <span class="dashicons dashicons-chart-area"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="chart-body">
                <div id="overview-chart" style="height: 400px;"></div>
            </div>
        </div>
        <?php
    }
    
    private function render_overview_sidebar(): void {
        ?>
        <div class="sl-ai-pro-sidebar">
            <div class="sidebar-section">
                <h4><?php _e('Quick Actions', 'sosyallift-ai-pro'); ?></h4>
                <div class="action-buttons">
                    <button class="button button-primary button-block" id="sl-ai-pro-run-analysis">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Run Analysis', 'sosyallift-ai-pro'); ?>
                    </button>
                    
                    <button class="button button-secondary button-block" id="sl-ai-pro-generate-report">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php _e('Generate Report', 'sosyallift-ai-pro'); ?>
                    </button>
                    
                    <button class="button button-secondary button-block" id="sl-ai-pro-find-keywords">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Find Keywords', 'sosyallift-ai-pro'); ?>
                    </button>
                    
                    <button class="button button-secondary button-block" id="sl-ai-pro-optimize-negatives">
                        <span class="dashicons dashicons-filter"></span>
                        <?php _e('Optimize Negatives', 'sosyallift-ai-pro'); ?>
                    </button>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h4><?php _e('Top Recommendations', 'sosyallift-ai-pro'); ?></h4>
                <div class="recommendations-list">
                    <?php $this->render_recommendations_list(); ?>
                </div>
            </div>
            
            <div class="sidebar-section">
                <h4><?php _e('System Status', 'sosyallift-ai-pro'); ?></h4>
                <div class="system-status">
                    <?php $this->render_system_status(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_recommendations_list(): void {
        $recommendations = $this->get_recommendations();
        
        if (empty($recommendations)) {
            echo '<p class="no-recommendations">' . __('No recommendations at this time.', 'sosyallift-ai-pro') . '</p>';
            return;
        }
        
        echo '<ul class="recommendations">';
        foreach ($recommendations as $rec) {
            echo '<li class="recommendation ' . esc_attr($rec['priority']) . '">';
            echo '<span class="dashicons dashicons-' . esc_attr($rec['icon']) . '"></span>';
            echo '<div class="recommendation-content">';
            echo '<strong>' . esc_html($rec['title']) . '</strong>';
            echo '<p>' . esc_html($rec['description']) . '</p>';
            if (!empty($rec['action'])) {
                echo '<button class="button button-small" data-action="' . esc_attr($rec['action']) . '">' . esc_html($rec['action_text']) . '</button>';
            }
            echo '</div>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    private function get_recommendations(): array {
        return [
            [
                'title'         => __('Optimize Negative Keywords', 'sosyallift-ai-pro'),
                'description'   => __('Add 15 new negative keywords to reduce wasted spend.', 'sosyallift-ai-pro'),
                'priority'      => 'high',
                'icon'          => 'warning',
                'action'        => 'optimize_negatives',
                'action_text'   => __('Optimize Now', 'sosyallift-ai-pro'),
            ],
            [
                'title'         => __('Increase Bids for High-Intent Keywords', 'sosyallift-ai-pro'),
                'description'   => __('12 commercial intent keywords need bid adjustments.', 'sosyallift-ai-pro'),
                'priority'      => 'medium',
                'icon'          => 'arrow-up-alt',
                'action'        => 'adjust_bids',
                'action_text'   => __('Adjust Bids', 'sosyallift-ai-pro'),
            ],
            [
                'title'         => __('Create Content for Informational Queries', 'sosyallift-ai-pro'),
                'description'   => __('5 informational keywords have high search volume.', 'sosyallift-ai-pro'),
                'priority'      => 'low',
                'icon'          => 'edit',
                'action'        => 'create_content',
                'action_text'   => __('Create Content', 'sosyallift-ai-pro'),
            ],
        ];
    }
    
    private function render_system_status(): void {
        $status = [
            'google_ads'    => $this->check_google_ads_status(),
            'seo_tracking'  => $this->check_seo_status(),
            'intent_analysis' => $this->check_intent_status(),
            'last_sync'     => $this->get_last_sync_time(),
        ];
        ?>
        <ul class="system-status-list">
            <li>
                <span class="status-label"><?php _e('Google Ads', 'sosyallift-ai-pro'); ?></span>
                <span class="status-indicator <?php echo $status['google_ads']['class']; ?>">
                    <span class="dashicons dashicons-<?php echo $status['google_ads']['icon']; ?>"></span>
                    <?php echo $status['google_ads']['text']; ?>
                </span>
            </li>
            <li>
                <span class="status-label"><?php _e('SEO Tracking', 'sosyallift-ai-pro'); ?></span>
                <span class="status-indicator <?php echo $status['seo_tracking']['class']; ?>">
                    <span class="dashicons dashicons-<?php echo $status['seo_tracking']['icon']; ?>"></span>
                    <?php echo $status['seo_tracking']['text']; ?>
                </span>
            </li>
            <li>
                <span class="status-label"><?php _e('Intent Analysis', 'sosyallift-ai-pro'); ?></span>
                <span class="status-indicator <?php echo $status['intent_analysis']['class']; ?>">
                    <span class="dashicons dashicons-<?php echo $status['intent_analysis']['icon']; ?>"></span>
                    <?php echo $status['intent_analysis']['text']; ?>
                </span>
            </li>
            <li>
                <span class="status-label"><?php _e('Last Sync', 'sosyallift-ai-pro'); ?></span>
                <span class="status-value"><?php echo $status['last_sync']; ?></span>
            </li>
        </ul>
        <?php
    }
    
    private function check_google_ads_status(): array {
        $token = get_option('sl_ai_pro_google_dev_token');
        $client_id = get_option('sl_ai_pro_google_client_id');
        $last_auth = get_option('sl_ai_pro_google_last_auth');
        
        if (empty($token) || empty($client_id)) {
            return [
                'class' => 'error',
                'icon'  => 'no',
                'text'  => __('Not Configured', 'sosyallift-ai-pro'),
            ];
        }
        
        if ($last_auth && (time() - $last_auth) < 86400) { // 24 hours
            return [
                'class' => 'success',
                'icon'  => 'yes',
                'text'  => __('Connected', 'sosyallift-ai-pro'),
            ];
        }
        
        return [
            'class' => 'warning',
            'icon'  => 'warning',
            'text'  => __('Needs Refresh', 'sosyallift-ai-pro'),
        ];
    }
    
    private function check_seo_status(): array {
        $key = get_option('sl_ai_pro_google_search_console_key');
        
        if (empty($key)) {
            return [
                'class' => 'warning',
                'icon'  => 'warning',
                'text'  => __('Not Configured', 'sosyallift-ai-pro'),
            ];
        }
        
        $last_fetch = get_option('sl_ai_pro_seo_last_fetch', 0);
        
        if ($last_fetch && (time() - $last_fetch) < 86400) { // 24 hours
            return [
                'class' => 'success',
                'icon'  => 'yes',
                'text'  => __('Active', 'sosyallift-ai-pro'),
            ];
        }
        
        return [
            'class' => 'error',
            'icon'  => 'no',
            'text'  => __('Not Syncing', 'sosyallift-ai-pro'),
        ];
    }
    
    private function check_intent_status(): array {
        $models = get_option('sl_ai_pro_intent_models', []);
        
        if (empty($models)) {
            return [
                'class' => 'warning',
                'icon'  => 'warning',
                'text'  => __('Training Needed', 'sosyallift-ai-pro'),
            ];
        }
        
        return [
            'class' => 'success',
            'icon'  => 'yes',
            'text'  => __('Active', 'sosyallift-ai-pro'),
        ];
    }
    
    private function get_last_sync_time(): string {
        $last_sync = get_option('sl_ai_pro_last_sync', 0);
        
        if (!$last_sync) {
            return __('Never', 'sosyallift-ai-pro');
        }
        
        return human_time_diff($last_sync) . ' ' . __('ago', 'sosyallift-ai-pro');
    }
    
    private function render_top_keywords(): void {
        $keywords = $this->get_top_keywords();
        ?>
        <div class="sl-ai-pro-widget">
            <div class="widget-header">
                <h3><?php _e('Top Keywords', 'sosyallift-ai-pro'); ?></h3>
                <div class="widget-actions">
                    <select id="keyword-source-filter">
                        <option value="all"><?php _e('All Sources', 'sosyallift-ai-pro'); ?></option>
                        <option value="ads"><?php _e('Ads Only', 'sosyallift-ai-pro'); ?></option>
                        <option value="seo"><?php _e('SEO Only', 'sosyallift-ai-pro'); ?></option>
                    </select>
                </div>
            </div>
            <div class="widget-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Keyword', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Source', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Clicks', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Intent', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Score', 'sosyallift-ai-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($keywords as $keyword): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($keyword['keyword']); ?></strong>
                                <?php if (!empty($keyword['trend'])): ?>
                                <span class="trend-indicator <?php echo $keyword['trend']['class']; ?>">
                                    <span class="dashicons dashicons-<?php echo $keyword['trend']['icon']; ?>"></span>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="source-badge source-<?php echo esc_attr($keyword['source']); ?>">
                                    <?php echo esc_html(ucfirst($keyword['source'])); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($keyword['clicks']); ?></td>
                            <td>
                                <span class="intent-badge intent-<?php echo esc_attr($keyword['intent']); ?>">
                                    <?php echo esc_html(ucfirst($keyword['intent'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="score-bar">
                                    <div class="score-fill" style="width: <?php echo esc_attr($keyword['score']); ?>%;"></div>
                                    <span class="score-text"><?php echo esc_html($keyword['score']); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="widget-footer">
                <a href="#keywords" class="button button-small"><?php _e('View All Keywords', 'sosyallift-ai-pro'); ?></a>
            </div>
        </div>
        <?php
    }
    
    private function get_top_keywords(int $limit = 10): array {
        global $wpdb;
        $range = $this->get_date_range_sql();
        
        $keywords = $wpdb->get_results($wpdb->prepare("
            SELECT 
                k.keyword,
                k.source,
                SUM(k.clicks) as clicks,
                k.intent,
                k.score,
                k.trend
            FROM {$wpdb->prefix}sl_ai_keywords k
            WHERE k.last_seen >= %s
            GROUP BY k.keyword, k.source, k.intent
            ORDER BY SUM(k.clicks) DESC
            LIMIT %d
        ", $range['start'], $limit), ARRAY_A);
        
        if (!$keywords) {
            return [];
        }
        
        $result = [];
        foreach ($keywords as $keyword) {
            $result[] = [
                'keyword'   => $keyword['keyword'],
                'source'    => $keyword['source'],
                'clicks'    => (int) $keyword['clicks'],
                'intent'    => $keyword['intent'] ?: 'unknown',
                'score'     => (int) $keyword['score'],
                'trend'     => $this->parse_trend($keyword['trend']),
            ];
        }
        
        return $result;
    }
    
    private function parse_trend(string $trend_json): array {
        $trend = json_decode($trend_json, true);
        
        if (!$trend) {
            return ['direction' => 'flat', 'value' => 0];
        }
        
        $value = $trend['value'] ?? 0;
        
        if ($value > 0) {
            return [
                'class' => 'positive',
                'icon'  => 'arrow-up-alt',
                'value' => abs($value),
            ];
        } elseif ($value < 0) {
            return [
                'class' => 'negative',
                'icon'  => 'arrow-down-alt',
                'value' => abs($value),
            ];
        }
        
        return [
            'class' => 'neutral',
            'icon'  => 'minus',
            'value' => 0,
        ];
    }
    
    private function render_top_pages(): void {
        $pages = $this->get_top_pages();
        ?>
        <div class="sl-ai-pro-widget">
            <div class="widget-header">
                <h3><?php _e('Top Performing Pages', 'sosyallift-ai-pro'); ?></h3>
                <div class="widget-actions">
                    <select id="page-metric-filter">
                        <option value="clicks"><?php _e('Clicks', 'sosyallift-ai-pro'); ?></option>
                        <option value="conversions"><?php _e('Conversions', 'sosyallift-ai-pro'); ?></option>
                        <option value="intent"><?php _e('Intent Score', 'sosyallift-ai-pro'); ?></option>
                    </select>
                </div>
            </div>
            <div class="widget-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Page', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Clicks', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Conv. Rate', 'sosyallift-ai-pro'); ?></th>
                            <th><?php _e('Intent', 'sosyallift-ai-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($page['url']); ?>" target="_blank">
                                    <?php echo esc_html($page['title']); ?>
                                </a>
                                <br>
                                <small class="text-muted"><?php echo esc_url($page['url']); ?></small>
                            </td>
                            <td><?php echo number_format($page['clicks']); ?></td>
                            <td>
                                <div class="conversion-rate">
                                    <span class="rate-value"><?php echo esc_html($page['conversion_rate']); ?>%</span>
                                    <div class="rate-bar">
                                        <div class="rate-fill" style="width: <?php echo min(100, $page['conversion_rate'] * 5); ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="intent-score">
                                    <?php echo esc_html($page['intent_score']); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="widget-footer">
                <button class="button button-small" id="sl-ai-pro-analyze-pages">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php _e('Analyze Pages', 'sosyallift-ai-pro'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    private function get_top_pages(int $limit = 10): array {
        global $wpdb;
        $range = $this->get_date_range_sql();
        
        $pages = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.url,
                p.title,
                SUM(p.clicks) as clicks,
                COUNT(c.id) as conversions,
                AVG(p.intent_score) as intent_score
            FROM {$wpdb->prefix}sl_ai_pages p
            LEFT JOIN {$wpdb->prefix}sl_ai_conversions c ON c.page_id = p.id
            WHERE p.last_updated >= %s
            GROUP BY p.url, p.title
            ORDER BY SUM(p.clicks) DESC
            LIMIT %d
        ", $range['start'], $limit), ARRAY_A);
        
        if (!$pages) {
            return [];
        }
        
        $result = [];
        foreach ($pages as $page) {
            $clicks = (int) $page['clicks'];
            $conversions = (int) $page['conversions'];
            $conversion_rate = $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0;
            
            $result[] = [
                'url'               => $page['url'],
                'title'             => $page['title'] ?: basename($page['url']),
                'clicks'            => $clicks,
                'conversions'       => $conversions,
                'conversion_rate'   => $conversion_rate,
                'intent_score'      => round((float) $page['intent_score'], 1),
            ];
        }
        
        return $result;
    }
    
    public function ajax_refresh_dashboard(): void {
        $this->security->verify_ajax_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'sosyallift-ai-pro')], 403);
        }
        
        $data_type = $_POST['data_type'] ?? 'all';
        $date_range = $_POST['date_range'] ?? 'last_7_days';
        
        // Clear cache for this user
        $cache_prefix = 'dashboard_' . get_current_user_id() . '_' . $date_range;
        $this->cache->delete_by_prefix($cache_prefix);
        
        $data = [];
        
        switch ($data_type) {
            case 'stats':
                $data['stats'] = $this->get_quick_stats();
                break;
                
            case 'keywords':
                $data['keywords'] = $this->get_top_keywords();
                break;
                
            case 'pages':
                $data['pages'] = $this->get_top_pages();
                break;
                
            case 'charts':
                $data['charts'] = $this->get_chart_data();
                break;
                
            default:
                $data = [
                    'stats'     => $this->get_quick_stats(),
                    'keywords'  => $this->get_top_keywords(),
                    'pages'     => $this->get_top_pages(),
                    'charts'    => $this->get_chart_data(),
                    'alerts'    => $this->get_alerts(),
                    'timestamp' => current_time('mysql'),
                ];
                break;
        }
        
        wp_send_json_success($data);
    }
    
    private function get_chart_data(): array {
        $range = $this->get_date_range_sql();
        
        global $wpdb;
        
        // Get daily data for the range
        $daily_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN source = 'ads' THEN clicks ELSE 0 END) as ads_clicks,
                SUM(CASE WHEN source = 'seo' THEN clicks ELSE 0 END) as seo_clicks,
                AVG(CASE WHEN intent_type = 'commercial' THEN 100 ELSE 0 END) as intent_score
            FROM {$wpdb->prefix}sl_ai_logs
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", $range['start']), ARRAY_A);
        
        $dates = [];
        $ads_data = [];
        $seo_data = [];
        $intent_data = [];
        
        foreach ($daily_data as $day) {
            $dates[] = date('M j', strtotime($day['date']));
            $ads_data[] = (int) $day['ads_clicks'];
            $seo_data[] = (int) $day['seo_clicks'];
            $intent_data[] = (float) $day['intent_score'];
        }
        
        // Fill missing dates
        $all_dates = $this->generate_date_range($range['start'], $range['end']);
        
        $complete_data = [];
        foreach ($all_dates as $date) {
            $date_str = $date->format('Y-m-d');
            $display_date = $date->format('M j');
            
            $index = array_search($date_str, array_column($daily_data, 'date'));
            
            if ($index !== false) {
                $complete_data[$display_date] = [
                    'ads'       => $ads_data[$index],
                    'seo'       => $seo_data[$index],
                    'intent'    => $intent_data[$index],
                ];
            } else {
                $complete_data[$display_date] = [
                    'ads'       => 0,
                    'seo'       => 0,
                    'intent'    => 0,
                ];
            }
        }
        
        return [
            'dates'     => array_keys($complete_data),
            'ads'       => array_column($complete_data, 'ads'),
            'seo'       => array_column($complete_data, 'seo'),
            'intent'    => array_column($complete_data, 'intent'),
        ];
    }
    
    private function generate_date_range(string $start, string $end): array {
        $dates = [];
        $current = new DateTime($start);
        $end_date = new DateTime($end);
        
        while ($current <= $end_date) {
            $dates[] = clone $current;
            $current->modify('+1 day');
        }
        
        return $dates;
    }
    
    public function ajax_export_dashboard(): void {
        $this->security->verify_ajax_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'sosyallift-ai-pro'), 403);
        }
        
        $format = $_POST['format'] ?? 'csv';
        $data_type = $_POST['data_type'] ?? 'all';
        $date_range = $_POST['date_range'] ?? 'last_7_days';
        
        switch ($format) {
            case 'csv':
                $this->export_csv($data_type, $date_range);
                break;
                
            case 'excel':
                $this->export_excel($data_type, $date_range);
                break;
                
            case 'pdf':
                $this->export_pdf($data_type, $date_range);
                break;
                
            case 'json':
                $this->export_json($data_type, $date_range);
                break;
                
            default:
                wp_send_json_error(['message' => __('Invalid export format', 'sosyallift-ai-pro')]);
        }
    }
    
    private function export_csv(string $data_type, string $date_range): void {
        $data = $this->get_export_data($data_type, $date_range);
        
        $filename = 'sosyallift-export-' . $data_type . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data) && is_array($data)) {
            $headers = array_keys(reset($data));
            fputcsv($output, $headers);
            
            // Add data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    private function get_export_data(string $data_type, string $date_range): array {
        global $wpdb;
        
        switch ($data_type) {
            case 'keywords':
                return $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        keyword,
                        source,
                        clicks,
                        impressions,
                        ctr,
                        cost,
                        conversions,
                        intent_type,
                        score,
                        last_seen
                    FROM {$wpdb->prefix}sl_ai_keywords
                    WHERE last_seen >= DATE_SUB(NOW(), INTERVAL %s DAY)
                    ORDER BY clicks DESC
                ", $this->get_days_from_range($date_range)), ARRAY_A);
                
            case 'performance':
                return $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        DATE(created_at) as date,
                        source,
                        SUM(clicks) as clicks,
                        SUM(impressions) as impressions,
                        AVG(ctr) as ctr,
                        SUM(cost) as cost,
                        SUM(conversions) as conversions,
                        AVG(intent_score) as intent_score
                    FROM {$wpdb->prefix}sl_ai_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
                    GROUP BY DATE(created_at), source
                    ORDER BY date DESC
                ", $this->get_days_from_range($date_range)), ARRAY_A);
                
            case 'recommendations':
                return $this->get_recommendations_export();
                
            default:
                return [];
        }
    }
    
    private function get_days_from_range(string $range): int {
        switch ($range) {
            case 'today': return 1;
            case 'yesterday': return 1;
            case 'last_7_days': return 7;
            case 'last_30_days': return 30;
            case 'last_90_days': return 90;
            case 'this_month': return date('t');
            case 'last_month': return date('t', strtotime('-1 month'));
            default: return 7;
        }
    }
    
    private function get_recommendations_export(): array {
        return [
            [
                'type'          => 'negative_keywords',
                'keyword'       => 'example keyword',
                'match_type'    => 'exact',
                'estimated_savings' => '$50',
                'priority'      => 'high',
                'action'        => 'add_to_campaign',
            ],
            // Add more recommendations...
        ];
    }
    
    public function ajax_get_widget_data(): void {
        $this->security->verify_ajax_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'sosyallift-ai-pro')], 403);
        }
        
        $widget = $_POST['widget'] ?? '';
        $params = $_POST['params'] ?? [];
        
        $method = 'get_widget_' . $widget;
        
        if (!method_exists($this, $method)) {
            wp_send_json_error(['message' => __('Invalid widget', 'sosyallift-ai-pro')]);
        }
        
        $data = call_user_func([$this, $method], $params);
        
        wp_send_json_success($data);
    }
    
    private function get_widget_performance_chart(array $params): array {
        return $this->get_chart_data();
    }
    
    private function get_widget_intent_distribution(array $params): array {
        global $wpdb;
        
        $distribution = $wpdb->get_results("
            SELECT 
                intent_type,
                COUNT(*) as count,
                AVG(score) as avg_score
            FROM {$wpdb->prefix}sl_ai_intent
            WHERE analyzed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY intent_type
            ORDER BY count DESC
        ", ARRAY_A);
        
        $labels = [];
        $data = [];
        $colors = [];
        
        $color_map = [
            'commercial'    => '#3b82f6',
            'informational' => '#10b981',
            'navigational'  => '#f59e0b',
            'transactional' => '#ef4444',
            'comparison'    => '#8b5cf6',
            'unknown'       => '#6b7280',
        ];
        
        foreach ($distribution as $item) {
            $labels[] = ucfirst($item['intent_type']);
            $data[] = (int) $item['count'];
            $colors[] = $color_map[$item['intent_type']] ?? '#6b7280';
        }
        
        return [
            'labels'    => $labels,
            'data'      => $data,
            'colors'    => $colors,
            'total'     => array_sum($data),
        ];
    }
    
    private function get_alerts(): array {
        $alerts = get_option('sl_ai_pro_alerts', []);
        
        // Filter only unread alerts from last 7 days
        $recent_alerts = array_filter($alerts, function($alert) {
            return $alert['read'] === false && 
                   strtotime($alert['created_at']) > strtotime('-7 days');
        });
        
        return array_values($recent_alerts);
    }
    
    private function is_setup_complete(): bool {
        $required = [
            'sl_ai_pro_google_dev_token',
            'sl_ai_pro_google_client_id',
            'sl_ai_pro_google_refresh_token',
        ];
        
        foreach ($required as $option) {
            if (empty(get_option($option))) {
                return false;
            }
        }
        
        return true;
    }
    
    private function render_setup_wizard(): void {
        ?>
        <div class="wrap sl-ai-pro-setup-wizard">
            <div class="wizard-container">
                <div class="wizard-header">
                    <h1><?php _e('Welcome to Sosyallift AI Pro', 'sosyallift-ai-pro'); ?></h1>
                    <p><?php _e('Let\'s get your AI Intelligence platform set up in a few steps.', 'sosyallift-ai-pro'); ?></p>
                </div>
                
                <div class="wizard-steps">
                    <div class="step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-title"><?php _e('Google Ads Connection', 'sosyallift-ai-pro'); ?></span>
                    </div>
                    <div class="step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-title"><?php _e('SEO Setup', 'sosyallift-ai-pro'); ?></span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-title"><?php _e('Website Configuration', 'sosyallift-ai-pro'); ?></span>
                    </div>
                    <div class="step" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-title"><?php _e('Initial Analysis', 'sosyallift-ai-pro'); ?></span>
                    </div>
                </div>
                
                <div class="wizard-content">
                    <div id="step-1" class="step-content active">
                        <?php $this->render_google_ads_setup(); ?>
                    </div>
                    <div id="step-2" class="step-content">
                        <?php $this->render_seo_setup(); ?>
                    </div>
                    <div id="step-3" class="step-content">
                        <?php $this->render_website_config(); ?>
                    </div>
                    <div id="step-4" class="step-content">
                        <?php $this->render_initial_analysis(); ?>
                    </div>
                </div>
                
                <div class="wizard-footer">
                    <button class="button button-secondary" id="wizard-prev"><?php _e('Previous', 'sosyallift-ai-pro'); ?></button>
                    <button class="button button-primary" id="wizard-next"><?php _e('Next', 'sosyallift-ai-pro'); ?></button>
                    <button class="button button-link" id="wizard-skip"><?php _e('Skip Setup', 'sosyallift-ai-pro'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_performance_tab(): void {
        ?>
        <div class="sl-ai-pro-tab-content">
            <div class="row">
                <div class="col-12">
                    <div class="performance-controls">
                        <div class="control-group">
                            <label for="performance-metric"><?php _e('Metric', 'sosyallift-ai-pro'); ?></label>
                            <select id="performance-metric" class="regular-text">
                                <option value="clicks"><?php _e('Clicks', 'sosyallift-ai-pro'); ?></option>
                                <option value="impressions"><?php _e('Impressions', 'sosyallift-ai-pro'); ?></option>
                                <option value="ctr"><?php _e('CTR', 'sosyallift-ai-pro'); ?></option>
                                <option value="cost"><?php _e('Cost', 'sosyallift-ai-pro'); ?></option>
                                <option value="conversions"><?php _e('Conversions', 'sosyallift-ai-pro'); ?></option>
                                <option value="roas"><?php _e('ROAS', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <label for="performance-breakdown"><?php _e('Breakdown', 'sosyallift-ai-pro'); ?></label>
                            <select id="performance-breakdown" class="regular-text">
                                <option value="source"><?php _e('By Source', 'sosyallift-ai-pro'); ?></option>
                                <option value="campaign"><?php _e('By Campaign', 'sosyallift-ai-pro'); ?></option>
                                <option value="device"><?php _e('By Device', 'sosyallift-ai-pro'); ?></option>
                                <option value="day"><?php _e('By Day', 'sosyallift-ai-pro'); ?></option>
                                <option value="week"><?php _e('By Week', 'sosyallift-ai-pro'); ?></option>
                                <option value="month"><?php _e('By Month', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="control-group">
                            <label for="performance-comparison"><?php _e('Compare With', 'sosyallift-ai-pro'); ?></label>
                            <select id="performance-comparison" class="regular-text">
                                <option value="none"><?php _e('None', 'sosyallift-ai-pro'); ?></option>
                                <option value="previous_period"><?php _e('Previous Period', 'sosyallift-ai-pro'); ?></option>
                                <option value="same_period_last_month"><?php _e('Same Period Last Month', 'sosyallift-ai-pro'); ?></option>
                                <option value="same_period_last_year"><?php _e('Same Period Last Year', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <button class="button button-primary" id="performance-apply"><?php _e('Apply', 'sosyallift-ai-pro'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-8">
                    <div class="performance-chart-container">
                        <div id="performance-chart" style="height: 500px;"></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="performance-summary">
                        <h4><?php _e('Performance Summary', 'sosyallift-ai-pro'); ?></h4>
                        <div id="performance-summary-content">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                    </div>
                    
                    <div class="performance-insights">
                        <h4><?php _e('Key Insights', 'sosyallift-ai-pro'); ?></h4>
                        <div id="performance-insights-content">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="performance-details">
                        <h4><?php _e('Detailed Performance', 'sosyallift-ai-pro'); ?></h4>
                        <table id="performance-table" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Source', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Clicks', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Impressions', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('CTR', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Cost', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Conversions', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('ROAS', 'sosyallift-ai-pro'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_keywords_tab(): void {
        ?>
        <div class="sl-ai-pro-tab-content">
            <div class="row">
                <div class="col-12">
                    <div class="keywords-header">
                        <h3><?php _e('Keyword Intelligence', 'sosyallift-ai-pro'); ?></h3>
                        <div class="keywords-actions">
                            <button class="button button-primary" id="keywords-import">
                                <span class="dashicons dashicons-upload"></span>
                                <?php _e('Import Keywords', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="keywords-export">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Export', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="keywords-analyze">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <?php _e('Analyze', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="keywords-find-new">
                                <span class="dashicons dashicons-search"></span>
                                <?php _e('Find New Keywords', 'sosyallift-ai-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="keywords-filters">
                        <div class="filter-group">
                            <input type="text" 
                                   id="keywords-search" 
                                   class="regular-text" 
                                   placeholder="<?php esc_attr_e('Search keywords...', 'sosyallift-ai-pro'); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="keywords-source"><?php _e('Source', 'sosyallift-ai-pro'); ?></label>
                            <select id="keywords-source" class="regular-text" multiple="multiple">
                                <option value="ads"><?php _e('Google Ads', 'sosyallift-ai-pro'); ?></option>
                                <option value="seo"><?php _e('Organic Search', 'sosyallift-ai-pro'); ?></option>
                                <option value="competitor"><?php _e('Competitor', 'sosyallift-ai-pro'); ?></option>
                                <option value="suggested"><?php _e('Suggested', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="keywords-intent"><?php _e('Intent', 'sosyallift-ai-pro'); ?></label>
                            <select id="keywords-intent" class="regular-text" multiple="multiple">
                                <option value="commercial"><?php _e('Commercial', 'sosyallift-ai-pro'); ?></option>
                                <option value="informational"><?php _e('Informational', 'sosyallift-ai-pro'); ?></option>
                                <option value="navigational"><?php _e('Navigational', 'sosyallift-ai-pro'); ?></option>
                                <option value="transactional"><?php _e('Transactional', 'sosyallift-ai-pro'); ?></option>
                                <option value="comparison"><?php _e('Comparison', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="keywords-status"><?php _e('Status', 'sosyallift-ai-pro'); ?></label>
                            <select id="keywords-status" class="regular-text" multiple="multiple">
                                <option value="active"><?php _e('Active', 'sosyallift-ai-pro'); ?></option>
                                <option value="paused"><?php _e('Paused', 'sosyallift-ai-pro'); ?></option>
                                <option value="negative"><?php _e('Negative', 'sosyallift-ai-pro'); ?></option>
                                <option value="monitoring"><?php _e('Monitoring', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="keywords-score"><?php _e('Score', 'sosyallift-ai-pro'); ?></label>
                            <select id="keywords-score" class="regular-text">
                                <option value=""><?php _e('All Scores', 'sosyallift-ai-pro'); ?></option>
                                <option value="high"><?php _e('High (80-100)', 'sosyallift-ai-pro'); ?></option>
                                <option value="medium"><?php _e('Medium (60-79)', 'sosyallift-ai-pro'); ?></option>
                                <option value="low"><?php _e('Low (0-59)', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <button class="button button-secondary" id="keywords-apply-filters"><?php _e('Apply Filters', 'sosyallift-ai-pro'); ?></button>
                        <button class="button button-link" id="keywords-reset-filters"><?php _e('Reset', 'sosyallift-ai-pro'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="keywords-table-container">
                        <table id="keywords-table" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th><?php _e('Keyword', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Source', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Intent', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Clicks', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Impressions', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('CTR', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Cost', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Conv. Rate', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Score', 'sosyallift-ai-pro'); ?></th>
                                    <th><?php _e('Actions', 'sosyallift-ai-pro'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_intent_tab(): void {
        ?>
        <div class="sl-ai-pro-tab-content">
            <div class="row">
                <div class="col-12">
                    <div class="intent-header">
                        <h3><?php _e('Intent Analysis', 'sosyallift-ai-pro'); ?></h3>
                        <div class="intent-actions">
                            <button class="button button-primary" id="intent-analyze-bulk">
                                <span class="dashicons dashicons-chart-pie"></span>
                                <?php _e('Bulk Analyze', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="intent-train-model">
                                <span class="dashicons dashicons-admin-tools"></span>
                                <?php _e('Train Model', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="intent-export-insights">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Export Insights', 'sosyallift-ai-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-8">
                    <div class="intent-chart-container">
                        <div id="intent-chart" style="height: 400px;"></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="intent-summary">
                        <h4><?php _e('Intent Summary', 'sosyallift-ai-pro'); ?></h4>
                        <div id="intent-summary-content">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="intent-analysis-tool">
                        <h4><?php _e('Real-time Intent Analysis', 'sosyallift-ai-pro'); ?></h4>
                        <div class="analysis-form">
                            <textarea id="intent-query" 
                                     class="large-text" 
                                     rows="3" 
                                     placeholder="<?php esc_attr_e('Enter a search query or phrase to analyze intent...', 'sosyallift-ai-pro'); ?>"></textarea>
                            <div class="analysis-controls">
                                <select id="intent-language" class="regular-text">
                                    <option value="tr"><?php _e('Turkish', 'sosyallift-ai-pro'); ?></option>
                                    <option value="en"><?php _e('English', 'sosyallift-ai-pro'); ?></option>
                                </select>
                                <button class="button button-primary" id="intent-analyze"><?php _e('Analyze', 'sosyallift-ai-pro'); ?></button>
                            </div>
                        </div>
                        
                        <div id="intent-results" class="analysis-results" style="display: none;">
                            <div class="results-header">
                                <h5><?php _e('Analysis Results', 'sosyallift-ai-pro'); ?></h5>
                                <button class="button button-small" id="intent-copy-results">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php _e('Copy', 'sosyallift-ai-pro'); ?>
                                </button>
                            </div>
                            <div id="intent-results-content"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="intent-insights">
                        <h4><?php _e('Intent Insights & Patterns', 'sosyallift-ai-pro'); ?></h4>
                        <div class="insights-grid">
                            <div class="insight-card">
                                <div class="insight-icon">
                                    <span class="dashicons dashicons-tag"></span>
                                </div>
                                <div class="insight-content">
                                    <h5><?php _e('Commercial Patterns', 'sosyallift-ai-pro'); ?></h5>
                                    <div id="commercial-patterns"></div>
                                </div>
                            </div>
                            
                            <div class="insight-card">
                                <div class="insight-icon">
                                    <span class="dashicons dashicons-book"></span>
                                </div>
                                <div class="insight-content">
                                    <h5><?php _e('Informational Patterns', 'sosyallift-ai-pro'); ?></h5>
                                    <div id="informational-patterns"></div>
                                </div>
                            </div>
                            
                            <div class="insight-card">
                                <div class="insight-icon">
                                    <span class="dashicons dashicons-location"></span>
                                </div>
                                <div class="insight-content">
                                    <h5><?php _e('Navigational Patterns', 'sosyallift-ai-pro'); ?></h5>
                                    <div id="navigational-patterns"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_recommendations_tab(): void {
        ?>
        <div class="sl-ai-pro-tab-content">
            <div class="row">
                <div class="col-12">
                    <div class="recommendations-header">
                        <h3><?php _e('AI Recommendations', 'sosyallift-ai-pro'); ?></h3>
                        <div class="recommendations-actions">
                            <button class="button button-primary" id="recommendations-generate">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Generate New', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="recommendations-apply-all">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Apply All', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="recommendations-export">
                                <span class="dashicons dashicons-download"></span>
                                <?php _e('Export', 'sosyallift-ai-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="recommendations-filters">
                        <div class="filter-group">
                            <label for="recommendations-category"><?php _e('Category', 'sosyallift-ai-pro'); ?></label>
                            <select id="recommendations-category" class="regular-text" multiple="multiple">
                                <option value="keywords"><?php _e('Keywords', 'sosyallift-ai-pro'); ?></option>
                                <option value="negative_keywords"><?php _e('Negative Keywords', 'sosyallift-ai-pro'); ?></option>
                                <option value="bids"><?php _e('Bid Adjustments', 'sosyallift-ai-pro'); ?></option>
                                <option value="content"><?php _e('Content', 'sosyallift-ai-pro'); ?></option>
                                <option value="landing_pages"><?php _e('Landing Pages', 'sosyallift-ai-pro'); ?></option>
                                <option value="technical"><?php _e('Technical SEO', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="recommendations-priority"><?php _e('Priority', 'sosyallift-ai-pro'); ?></label>
                            <select id="recommendations-priority" class="regular-text" multiple="multiple">
                                <option value="high"><?php _e('High', 'sosyallift-ai-pro'); ?></option>
                                <option value="medium"><?php _e('Medium', 'sosyallift-ai-pro'); ?></option>
                                <option value="low"><?php _e('Low', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="recommendations-impact"><?php _e('Estimated Impact', 'sosyallift-ai-pro'); ?></label>
                            <select id="recommendations-impact" class="regular-text">
                                <option value=""><?php _e('All Impacts', 'sosyallift-ai-pro'); ?></option>
                                <option value="high"><?php _e('High Impact', 'sosyallift-ai-pro'); ?></option>
                                <option value="medium"><?php _e('Medium Impact', 'sosyallift-ai-pro'); ?></option>
                                <option value="low"><?php _e('Low Impact', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="recommendations-effort"><?php _e('Effort Required', 'sosyallift-ai-pro'); ?></label>
                            <select id="recommendations-effort" class="regular-text">
                                <option value=""><?php _e('All Efforts', 'sosyallift-ai-pro'); ?></option>
                                <option value="low"><?php _e('Low Effort', 'sosyallift-ai-pro'); ?></option>
                                <option value="medium"><?php _e('Medium Effort', 'sosyallift-ai-pro'); ?></option>
                                <option value="high"><?php _e('High Effort', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <button class="button button-secondary" id="recommendations-apply-filters"><?php _e('Apply Filters', 'sosyallift-ai-pro'); ?></button>
                        <button class="button button-link" id="recommendations-reset-filters"><?php _e('Reset', 'sosyallift-ai-pro'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="recommendations-stats">
                        <div class="stat">
                            <span class="stat-value" id="total-recommendations">0</span>
                            <span class="stat-label"><?php _e('Total Recommendations', 'sosyallift-ai-pro'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="high-priority">0</span>
                            <span class="stat-label"><?php _e('High Priority', 'sosyallift-ai-pro'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="estimated-impact">$0</span>
                            <span class="stat-label"><?php _e('Estimated Impact', 'sosyallift-ai-pro'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-value" id="applied-recommendations">0</span>
                            <span class="stat-label"><?php _e('Applied', 'sosyallift-ai-pro'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="recommendations-list-container">
                        <div id="recommendations-list">
                            <!-- Recommendations will be loaded here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_alerts_tab(): void {
        ?>
        <div class="sl-ai-pro-tab-content">
            <div class="row">
                <div class="col-12">
                    <div class="alerts-header">
                        <h3><?php _e('Alerts & Notifications', 'sosyallift-ai-pro'); ?></h3>
                        <div class="alerts-actions">
                            <button class="button button-secondary" id="alerts-mark-all-read">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Mark All as Read', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-secondary" id="alerts-clear-all">
                                <span class="dashicons dashicons-trash"></span>
                                <?php _e('Clear All', 'sosyallift-ai-pro'); ?>
                            </button>
                            <button class="button button-primary" id="alerts-settings">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Alert Settings', 'sosyallift-ai-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="alerts-filters">
                        <div class="filter-group">
                            <label for="alerts-type"><?php _e('Type', 'sosyallift-ai-pro'); ?></label>
                            <select id="alerts-type" class="regular-text" multiple="multiple">
                                <option value="performance"><?php _e('Performance', 'sosyallift-ai-pro'); ?></option>
                                <option value="keywords"><?php _e('Keywords', 'sosyallift-ai-pro'); ?></option>
                                <option value="intent"><?php _e('Intent', 'sosyallift-ai-pro'); ?></option>
                                <option value="technical"><?php _e('Technical', 'sosyallift-ai-pro'); ?></option>
                                <option value="system"><?php _e('System', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="alerts-severity"><?php _e('Severity', 'sosyallift-ai-pro'); ?></label>
                            <select id="alerts-severity" class="regular-text" multiple="multiple">
                                <option value="critical"><?php _e('Critical', 'sosyallift-ai-pro'); ?></option>
                                <option value="high"><?php _e('High', 'sosyallift-ai-pro'); ?></option>
                                <option value="medium"><?php _e('Medium', 'sosyallift-ai-pro'); ?></option>
                                <option value="low"><?php _e('Low', 'sosyallift-ai-pro'); ?></option>
                                <option value="info"><?php _e('Info', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="alerts-status"><?php _e('Status', 'sosyallift-ai-pro'); ?></label>
                            <select id="alerts-status" class="regular-text" multiple="multiple">
                                <option value="unread"><?php _e('Unread', 'sosyallift-ai-pro'); ?></option>
                                <option value="read"><?php _e('Read', 'sosyallift-ai-pro'); ?></option>
                                <option value="resolved"><?php _e('Resolved', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="alerts-date"><?php _e('Date Range', 'sosyallift-ai-pro'); ?></label>
                            <select id="alerts-date" class="regular-text">
                                <option value="all"><?php _e('All Time', 'sosyallift-ai-pro'); ?></option>
                                <option value="today"><?php _e('Today', 'sosyallift-ai-pro'); ?></option>
                                <option value="yesterday"><?php _e('Yesterday', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_7_days"><?php _e('Last 7 Days', 'sosyallift-ai-pro'); ?></option>
                                <option value="last_30_days"><?php _e('Last 30 Days', 'sosyallift-ai-pro'); ?></option>
                            </select>
                        </div>
                        
                        <button class="button button-secondary" id="alerts-apply-filters"><?php _e('Apply Filters', 'sosyallift-ai-pro'); ?></button>
                        <button class="button button-link" id="alerts-reset-filters"><?php _e('Reset', 'sosyallift-ai-pro'); ?></button>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="alerts-container">
                        <div id="alerts-list">
                            <!-- Alerts will be loaded here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_modals(): void {
        ?>
        <!-- Keywords Import Modal -->
        <div id="keywords-import-modal" class="sl-ai-pro-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Import Keywords', 'sosyallift-ai-pro'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="import-methods">
                        <div class="import-method">
                            <h4><span class="dashicons dashicons-upload"></span> <?php _e('Upload CSV File', 'sosyallift-ai-pro'); ?></h4>
                            <p><?php _e('Upload a CSV file containing your keywords.', 'sosyallift-ai-pro'); ?></p>
                            <input type="file" id="keywords-csv-file" accept=".csv">
                            <small><?php _e('CSV should contain columns: keyword, source, match_type (optional)', 'sosyallift-ai-pro'); ?></small>
                        </div>
                        
                        <div class="import-method">
                            <h4><span class="dashicons dashicons-google"></span> <?php _e('Import from Google Ads', 'sosyallift-ai-pro'); ?></h4>
                            <p><?php _e('Import keywords directly from your Google Ads account.', 'sosyallift-ai-pro'); ?></p>
                            <button class="button button-primary" id="import-from-google-ads"><?php _e('Connect & Import', 'sosyallift-ai-pro'); ?></button>
                        </div>
                        
                        <div class="import-method">
                            <h4><span class="dashicons dashicons-admin-site"></span> <?php _e('Import from Google Search Console', 'sosyallift-ai-pro'); ?></h4>
                            <p><?php _e('Import organic keywords from Google Search Console.', 'sosyallift-ai-pro'); ?></p>
                            <button class="button button-primary" id="import-from-search-console"><?php _e('Connect & Import', 'sosyallift-ai-pro'); ?></button>
                        </div>
                    </div>
                    
                    <div class="import-options" style="display: none;">
                        <h4><?php _e('Import Options', 'sosyallift-ai-pro'); ?></h4>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="import-run-analysis" checked>
                                <?php _e('Run intent analysis on imported keywords', 'sosyallift-ai-pro'); ?>
                            </label>
                        </div>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="import-check-duplicates" checked>
                                <?php _e('Check for duplicate keywords', 'sosyallift-ai-pro'); ?>
                            </label>
                        </div>
                        <div class="option-group">
                            <label>
                                <input type="checkbox" id="import-add-to-campaigns" checked>
                                <?php _e('Add eligible keywords to campaigns', 'sosyallift-ai-pro'); ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="button button-secondary modal-close"><?php _e('Cancel', 'sosyallift-ai-pro'); ?></button>
                    <button class="button button-primary" id="keywords-import-start" disabled><?php _e('Start Import', 'sosyallift-ai-pro'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Analysis Results Modal -->
        <div id="analysis-results-modal" class="sl-ai-pro-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Analysis Results', 'sosyallift-ai-pro'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="analysis-results-content"></div>
                </div>
                <div class="modal-footer">
                    <button class="button button-secondary modal-close"><?php _e('Close', 'sosyallift-ai-pro'); ?></button>
                    <button class="button button-primary" id="analysis-export-results"><?php _e('Export Results', 'sosyallift-ai-pro'); ?></button>
                </div>
            </div>
        </div>
        
        <!-- Alert Settings Modal -->
        <div id="alert-settings-modal" class="sl-ai-pro-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Alert Settings', 'sosyallift-ai-pro'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert-settings-form">
                        <h4><?php _e('Alert Types', 'sosyallift-ai-pro'); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th><?php _e('Performance Alerts', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="alerts[performance][enabled]" checked>
                                            <?php _e('Enable performance alerts', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="alert-options">
                                            <label>
                                                <input type="checkbox" name="alerts[performance][ctr_drop]" checked>
                                                <?php _e('CTR drop > 20%', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[performance][cost_increase]" checked>
                                                <?php _e('Cost increase > 30%', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[performance][conversion_drop]" checked>
                                                <?php _e('Conversion drop > 25%', 'sosyallift-ai-pro'); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('Keyword Alerts', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="alerts[keywords][enabled]" checked>
                                            <?php _e('Enable keyword alerts', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="alert-options">
                                            <label>
                                                <input type="checkbox" name="alerts[keywords][new_opportunities]" checked>
                                                <?php _e('New keyword opportunities', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[keywords][wasted_spend]" checked>
                                                <?php _e('High wasted spend keywords', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[keywords][competitor_keywords]" checked>
                                                <?php _e('Competitor keyword activity', 'sosyallift-ai-pro'); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('Intent Alerts', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="alerts[intent][enabled]" checked>
                                            <?php _e('Enable intent alerts', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="alert-options">
                                            <label>
                                                <input type="checkbox" name="alerts[intent][intent_shift]" checked>
                                                <?php _e('Significant intent shift', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[intent][high_commercial]" checked>
                                                <?php _e('High commercial intent detected', 'sosyallift-ai-pro'); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('System Alerts', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="alerts[system][enabled]" checked>
                                            <?php _e('Enable system alerts', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="alert-options">
                                            <label>
                                                <input type="checkbox" name="alerts[system][sync_failed]" checked>
                                                <?php _e('Data sync failures', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[system][api_limit]" checked>
                                                <?php _e('API limit warnings', 'sosyallift-ai-pro'); ?>
                                            </label>
                                            <label>
                                                <input type="checkbox" name="alerts[system][storage]" checked>
                                                <?php _e('Storage warnings', 'sosyallift-ai-pro'); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h4><?php _e('Notification Methods', 'sosyallift-ai-pro'); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th><?php _e('Email Notifications', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="notifications[email][enabled]" checked>
                                            <?php _e('Send email notifications', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="notification-options">
                                            <input type="email" 
                                                   name="notifications[email][address]" 
                                                   class="regular-text" 
                                                   placeholder="<?php esc_attr_e('Email address', 'sosyallift-ai-pro'); ?>" 
                                                   value="<?php echo esc_attr(get_option('admin_email')); ?>">
                                            <select name="notifications[email][frequency]" class="regular-text">
                                                <option value="immediate"><?php _e('Immediate', 'sosyallift-ai-pro'); ?></option>
                                                <option value="hourly"><?php _e('Hourly Digest', 'sosyallift-ai-pro'); ?></option>
                                                <option value="daily"><?php _e('Daily Digest', 'sosyallift-ai-pro'); ?></option>
                                                <option value="weekly"><?php _e('Weekly Digest', 'sosyallift-ai-pro'); ?></option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('Dashboard Notifications', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="notifications[dashboard][enabled]" checked>
                                            <?php _e('Show in dashboard', 'sosyallift-ai-pro'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('Slack/Webhook', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="notifications[webhook][enabled]">
                                            <?php _e('Send to webhook', 'sosyallift-ai-pro'); ?>
                                        </label>
                                        <div class="notification-options">
                                            <input type="url" 
                                                   name="notifications[webhook][url]" 
                                                   class="regular-text" 
                                                   placeholder="<?php esc_attr_e('Webhook URL', 'sosyallift-ai-pro'); ?>">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h4><?php _e('Alert Thresholds', 'sosyallift-ai-pro'); ?></h4>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th><?php _e('Minimum Alert Severity', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <select name="thresholds[min_severity]" class="regular-text">
                                            <option value="info"><?php _e('Info (All alerts)', 'sosyallift-ai-pro'); ?></option>
                                            <option value="low"><?php _e('Low and above', 'sosyallift-ai-pro'); ?></option>
                                            <option value="medium" selected><?php _e('Medium and above', 'sosyallift-ai-pro'); ?></option>
                                            <option value="high"><?php _e('High and above', 'sosyallift-ai-pro'); ?></option>
                                            <option value="critical"><?php _e('Critical only', 'sosyallift-ai-pro'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><?php _e('Cooldown Period', 'sosyallift-ai-pro'); ?></th>
                                    <td>
                                        <input type="number" 
                                               name="thresholds[cooldown]" 
                                               class="small-text" 
                                               value="300" 
                                               min="60">
                                        <span class="description"><?php _e('seconds between similar alerts', 'sosyallift-ai-pro'); ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="button button-secondary modal-close"><?php _e('Cancel', 'sosyallift-ai-pro'); ?></button>
                    <button class="button button-primary" id="save-alert-settings"><?php _e('Save Settings', 'sosyallift-ai-pro'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function get_current_tab(): string {
        return $_GET['tab'] ?? 'overview';
    }
    
    private function get_date_range(): string {
        return $_GET['date_range'] ?? 'last_7_days';
    }
    
    private function get_refresh_interval(): int {
        return (int) get_user_meta(get_current_user_id(), 'sl_ai_pro_refresh_interval', true) ?: 300;
    }
    
    private function get_user_preferences(): array {
        $user_id = get_current_user_id();
        
        return [
            'dark_mode'     => (bool) get_user_meta($user_id, 'sl_ai_pro_dark_mode', true),
            'compact_view'  => (bool) get_user_meta($user_id, 'sl_ai_pro_compact_view', true),
            'auto_refresh'  => (bool) get_user_meta($user_id, 'sl_ai_pro_auto_refresh', true),
            'notifications' => (bool) get_user_meta($user_id, 'sl_ai_pro_notifications', true),
        ];
    }
    
    private function get_widget_config(): array {
        $user_id = get_current_user_id();
        $config = get_user_meta($user_id, 'sl_ai_pro_widget_config', true);
        
        if (!$config) {
            $config = [
                'overview' => [
                    'performance_chart' => true,
                    'top_keywords' => true,
                    'top_pages' => true,
                    'quick_stats' => true,
                    'recommendations' => true,
                    'system_status' => true,
                ],
                'performance' => [
                    'metric_chart' => true,
                    'breakdown_chart' => true,
                    'comparison_chart' => true,
                    'summary' => true,
                    'insights' => true,
                    'detailed_table' => true,
                ],
                // ... other tabs
            ];
        }
        
        return $config;
    }
    
    private function get_i18n_strings(): array {
        return [
            'loading' => __('Loading...', 'sosyallift-ai-pro'),
            'error' => __('Error', 'sosyallift-ai-pro'),
            'success' => __('Success', 'sosyallift-ai-pro'),
            'confirm' => __('Are you sure?', 'sosyallift-ai-pro'),
            'no_data' => __('No data available', 'sosyallift-ai-pro'),
            'apply' => __('Apply', 'sosyallift-ai-pro'),
            'cancel' => __('Cancel', 'sosyallift-ai-pro'),
            'save' => __('Save', 'sosyallift-ai-pro'),
            'delete' => __('Delete', 'sosyallift-ai-pro'),
            'edit' => __('Edit', 'sosyallift-ai-pro'),
            'view' => __('View', 'sosyallift-ai-pro'),
            'refresh' => __('Refresh', 'sosyallift-ai-pro'),
            'export' => __('Export', 'sosyallift-ai-pro'),
            'import' => __('Import', 'sosyallift-ai-pro'),
            'analyze' => __('Analyze', 'sosyallift-ai-pro'),
            'optimize' => __('Optimize', 'sosyallift-ai-pro'),
            'settings' => __('Settings', 'sosyallift-ai-pro'),
            'help' => __('Help', 'sosyallift-ai-pro'),
            'close' => __('Close', 'sosyallift-ai-pro'),
            'back' => __('Back', 'sosyallift-ai-pro'),
            'next' => __('Next', 'sosyallift-ai-pro'),
            'previous' => __('Previous', 'sosyallift-ai-pro'),
            'search' => __('Search', 'sosyallift-ai-pro'),
            'filter' => __('Filter', 'sosyallift-ai-pro'),
            'sort' => __('Sort', 'sosyallift-ai-pro'),
            'asc' => __('Ascending', 'sosyallift-ai-pro'),
            'desc' => __('Descending', 'sosyallift-ai-pro'),
            'all' => __('All', 'sosyallift-ai-pro'),
            'none' => __('None', 'sosyallift-ai-pro'),
            'select_all' => __('Select All', 'sosyallift-ai-pro'),
            'deselect_all' => __('Deselect All', 'sosyallift-ai-pro'),
            'selected' => __('Selected', 'sosyallift-ai-pro'),
            'items' => __('items', 'sosyallift-ai-pro'),
            'of' => __('of', 'sosyallift-ai-pro'),
            'page' => __('Page', 'sosyallift-ai-pro'),
            'rows_per_page' => __('Rows per page', 'sosyallift-ai-pro'),
            'showing' => __('Showing', 'sosyallift-ai-pro'),
            'to' => __('to', 'sosyallift-ai-pro'),
            'entries' => __('entries', 'sosyallift-ai-pro'),
            'no_results' => __('No results found', 'sosyallift-ai-pro'),
            'try_again' => __('Try again with different filters', 'sosyallift-ai-pro'),
        ];
    }
    
    private function add_custom_styles(): void {
        $styles = '';
        
        // Add user preference for dark mode
        if ($this->get_user_preferences()['dark_mode']) {
            $styles .= '
                .sl-ai-pro-dashboard {
                    --bg-primary: #1a1a1a;
                    --bg-secondary: #2d2d2d;
                    --text-primary: #ffffff;
                    --text-secondary: #cccccc;
                    --border-color: #404040;
                }
            ';
        }
        
        if ($styles) {
            wp_add_inline_style('sl-ai-pro-admin', $styles);
        }
    }
    
    private function get_help_content(string $tab): string {
        $content = [
            'overview' => '
                <h3>' . __('Dashboard Overview', 'sosyallift-ai-pro') . '</h3>
                <p>' . __('This dashboard provides a comprehensive view of your SEO and Ads performance, combined with AI-powered insights.', 'sosyallift-ai-pro') . '</p>
                <ul>
                    <li><strong>' . __('Quick Stats', 'sosyallift-ai-pro') . ':</strong> ' . __('Key metrics at a glance with trend indicators.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Performance Chart', 'sosyallift-ai-pro') . ':</strong> ' . __('Visualize trends over time for Ads, SEO, and Intent metrics.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Top Keywords', 'sosyallift-ai-pro') . ':</strong> ' . __('Your best-performing keywords across all sources.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Recommendations', 'sosyallift-ai-pro') . ':</strong> ' . __('AI-generated suggestions to improve performance.', 'sosyallift-ai-pro') . '</li>
                </ul>
            ',
            'metrics' => '
                <h3>' . __('Metrics Explained', 'sosyallift-ai-pro') . '</h3>
                <p>' . __('Understanding the key metrics in the dashboard:', 'sosyallift-ai-pro') . '</p>
                <ul>
                    <li><strong>' . __('CTR (Click-Through Rate)', 'sosyallift-ai-pro') . ':</strong> ' . __('Percentage of impressions that resulted in clicks.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Conversion Rate', 'sosyallift-ai-pro') . ':</strong> ' . __('Percentage of clicks that resulted in conversions.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('ROAS (Return on Ad Spend)', 'sosyallift-ai-pro') . ':</strong> ' . __('Revenue generated per currency unit spent on ads.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Commercial Intent', 'sosyallift-ai-pro') . ':</strong> ' . __('Percentage of queries showing purchase intent.', 'sosyallift-ai-pro') . '</li>
                    <li><strong>' . __('Keyword Score', 'sosyallift-ai-pro') . ':</strong> ' . __('AI-generated score (0-100) indicating keyword potential.', 'sosyallift-ai-pro') . '</li>
                </ul>
            ',
        ];
        
        return $content[$tab] ?? '';
    }
    
    private function get_help_sidebar(): string {
        return '
            <p><strong>' . __('For more information:', 'sosyallift-ai-pro') . '</strong></p>
            <p><a href="https://sosyallift.com/docs" target="_blank">' . __('Documentation', 'sosyallift-ai-pro') . '</a></p>
            <p><a href="https://sosyallift.com/support" target="_blank">' . __('Support', 'sosyallift-ai-pro') . '</a></p>
            <p><a href="https://sosyallift.com/tutorials" target="_blank">' . __('Video Tutorials', 'sosyallift-ai-pro') . '</a></p>
        ';
    }
}