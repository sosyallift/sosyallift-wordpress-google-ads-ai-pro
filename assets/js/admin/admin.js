(function($) {
    'use strict';
    
    const SL_AI_PRO = window.sl_ai_pro_dashboard || {};
    let charts = {};
    let dataTables = {};
    let refreshInterval = null;
    
    class Dashboard {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initCharts();
            this.initDataTables();
            this.loadDashboardData();
            this.startAutoRefresh();
            this.initSortableWidgets();
            this.initDarkMode();
        }
        
        bindEvents() {
            // Tab navigation
            $(document).on('click', '.nav-tab', (e) => {
                e.preventDefault();
                this.switchTab($(e.currentTarget));
            });
            
            // Refresh button
            $('#sl-ai-pro-refresh').on('click', () => this.refreshDashboard());
            
            // Export button
            $('#sl-ai-pro-export').on('click', () => this.exportDashboard());
            
            // Date range selector
            $('#sl-ai-pro-date-range').on('change', (e) => {
                this.handleDateRangeChange($(e.target).val());
            });
            
            // Custom date range
            $('#sl-ai-pro-apply-custom').on('click', () => this.applyCustomDateRange());
            
            // Quick action buttons
            $('#sl-ai-pro-run-analysis').on('click', () => this.runAnalysis());
            $('#sl-ai-pro-generate-report').on('click', () => this.generateReport());
            $('#sl-ai-pro-find-keywords').on('click', () => this.findKeywords());
            $('#sl-ai-pro-optimize-negatives').on('click', () => this.optimizeNegatives());
            $('#sl-ai-pro-analyze-pages').on('click', () => this.analyzePages());
            
            // Chart type buttons
            $('.chart-type-btn').on('click', (e) => {
                this.changeChartType($(e.currentTarget));
            });
            
            // Modal handling
            $(document).on('click', '.modal-close, .sl-ai-pro-modal', (e) => {
                if ($(e.target).hasClass('sl-ai-pro-modal') || $(e.target).hasClass('modal-close')) {
                    this.closeModal($(e.target).closest('.sl-ai-pro-modal'));
                }
            });
            
            // Keywords import modal
            $('#keywords-import').on('click', () => this.openKeywordsImportModal());
            $('#keywords-csv-file').on('change', (e) => this.handleFileSelect(e));
            $('#keywords-import-start').on('click', () => this.startKeywordsImport());
            
            // Performance filters
            $('#performance-apply').on('click', () => this.applyPerformanceFilters());
            
            // Keywords filters
            $('#keywords-apply-filters').on('click', () => this.applyKeywordsFilters());
            $('#keywords-reset-filters').on('click', () => this.resetKeywordsFilters());
            
            // Intent analysis
            $('#intent-analyze').on('click', () => this.analyzeIntent());
            $('#intent-copy-results').on('click', () => this.copyIntentResults());
            $('#intent-analyze-bulk').on('click', () => this.analyzeIntentBulk());
            
            // Recommendations
            $('#recommendations-generate').on('click', () => this.generateRecommendations());
            $('#recommendations-apply-all').on('click', () => this.applyAllRecommendations());
            
            // Alerts
            $('#alerts-mark-all-read').on('click', () => this.markAllAlertsRead());
            $('#alerts-clear-all').on('click', () => this.clearAllAlerts());
            $('#alerts-settings').on('click', () => this.openAlertSettings());
            $('#save-alert-settings').on('click', () => this.saveAlertSettings());
            
            // Widget controls
            $(document).on('click', '.widget-toggle', (e) => {
                this.toggleWidget($(e.currentTarget).closest('.sl-ai-pro-widget'));
            });
            
            $(document).on('click', '.widget-config', (e) => {
                e.preventDefault();
                this.configureWidget($(e.currentTarget).closest('.sl-ai-pro-widget'));
            });
            
            // Real-time updates
            if (SL_AI_PRO.user_preferences.auto_refresh) {
                $(document).on('visibilitychange', () => {
                    if (!document.hidden) {
                        this.refreshDashboard('visible');
                    }
                });
            }
        }
        
        switchTab(tabButton) {
            const tabId = tabButton.attr('href').substring(1);
            
            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            tabButton.addClass('nav-tab-active');
            
            // Show active content
            $('.tab-content').removeClass('active');
            $(`#${tabId}`).addClass('active');
            
            // Load tab-specific data if needed
            this.loadTabData(tabId);
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
        }
        
        loadTabData(tabId) {
            switch(tabId) {
                case 'performance':
                    if (!dataTables.performance) {
                        this.initPerformanceTable();
                    }
                    this.loadPerformanceChart();
                    break;
                    
                case 'keywords':
                    if (!dataTables.keywords) {
                        this.initKeywordsTable();
                    }
                    break;
                    
                case 'intent':
                    this.loadIntentChart();
                    this.loadIntentPatterns();
                    break;
                    
                case 'recommendations':
                    this.loadRecommendations();
                    break;
                    
                case 'alerts':
                    this.loadAlerts();
                    break;
            }
        }
        
        initCharts() {
            // Overview chart
            if ($('#overview-chart').length) {
                charts.overview = this.createOverviewChart();
            }
            
            // Performance chart
            if ($('#performance-chart').length) {
                charts.performance = this.createPerformanceChart();
            }
            
            // Intent chart
            if ($('#intent-chart').length) {
                charts.intent = this.createIntentChart();
            }
        }
        
        createOverviewChart() {
            const element = document.getElementById('overview-chart');
            
            return new ApexCharts(element, {
                series: [
                    { name: 'Ads Clicks', data: [] },
                    { name: 'SEO Clicks', data: [] },
                    { name: 'Intent Score', data: [] }
                ],
                chart: {
                    type: 'line',
                    height: '100%',
                    toolbar: { show: true },
                    zoom: { enabled: true }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                colors: ['#3b82f6', '#10b981', '#f59e0b'],
                xaxis: { categories: [], type: 'datetime' },
                yaxis: [
                    { title: { text: 'Clicks' } },
                    { opposite: true, title: { text: 'Intent Score %' } }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(value, { seriesIndex }) {
                            if (seriesIndex === 2) {
                                return value.toFixed(1) + '%';
                            }
                            return value.toLocaleString();
                        }
                    }
                },
                legend: { show: true },
                grid: { borderColor: '#f1f1f1' }
            });
        }
        
        createPerformanceChart() {
            const element = document.getElementById('performance-chart');
            
            return new ApexCharts(element, {
                series: [],
                chart: {
                    type: 'line',
                    height: '100%',
                    toolbar: { show: true }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                xaxis: { categories: [] },
                yaxis: { title: { text: 'Value' } },
                tooltip: { shared: true, intersect: false },
                legend: { show: true },
                grid: { borderColor: '#f1f1f1' }
            });
        }
        
        createIntentChart() {
            const element = document.getElementById('intent-chart');
            
            return new ApexCharts(element, {
                series: [{ data: [] }],
                chart: {
                    type: 'donut',
                    height: '100%'
                },
                labels: [],
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'],
                legend: { 
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: { show: true },
                                value: { 
                                    show: true,
                                    formatter: function(val) {
                                        return val + '%';
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + '%';
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + '%';
                        }
                    }
                }
            });
        }
        
        initDataTables() {
            // Performance table
            if ($('#performance-table').length) {
                this.initPerformanceTable();
            }
            
            // Keywords table
            if ($('#keywords-table').length) {
                this.initKeywordsTable();
            }
        }
        
        initPerformanceTable() {
            dataTables.performance = $('#performance-table').DataTable({
                ajax: {
                    url: SL_AI_PRO.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_ai_pro_get_widget_data',
                        widget: 'performance_table',
                        nonce: SL_AI_PRO.nonce
                    }
                },
                columns: [
                    { data: 'date' },
                    { data: 'source' },
                    { data: 'clicks', render: $.fn.dataTable.render.number(',', '.', 0, '') },
                    { data: 'impressions', render: $.fn.dataTable.render.number(',', '.', 0, '') },
                    { data: 'ctr', render: function(data) { return data.toFixed(2) + '%'; } },
                    { data: 'cost', render: function(data) { return SL_AI_PRO.currency + ' ' + data.toFixed(2); } },
                    { data: 'conversions', render: $.fn.dataTable.render.number(',', '.', 0, '') },
                    { data: 'roas', render: function(data) { return data.toFixed(2); } }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                dom: '<"top"flp>rt<"bottom"ip><"clear">',
                language: {
                    search: SL_AI_PRO.i18n.search + ':',
                    lengthMenu: SL_AI_PRO.i18n.rows_per_page + ' _MENU_',
                    info: SL_AI_PRO.i18n.showing + ' _START_ ' + SL_AI_PRO.i18n.to + ' _END_ ' + SL_AI_PRO.i18n.of + ' _TOTAL_ ' + SL_AI_PRO.i18n.entries,
                    paginate: {
                        first: '«',
                        last: '»',
                        next: '›',
                        previous: '‹'
                    }
                }
            });
        }
        
        initKeywordsTable() {
            dataTables.keywords = $('#keywords-table').DataTable({
                ajax: {
                    url: SL_AI_PRO.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'sl_ai_pro_get_widget_data',
                        widget: 'keywords_table',
                        nonce: SL_AI_PRO.nonce
                    }
                },
                columns: [
                    { 
                        data: 'keyword',
                        render: function(data, type, row) {
                            return '<strong>' + data + '</strong>' + 
                                   (row.trend ? '<span class="trend-indicator ' + row.trend.class + '">' +
                                   '<span class="dashicons dashicons-' + row.trend.icon + '"></span></span>' : '');
                        }
                    },
                    { 
                        data: 'source',
                        render: function(data) {
                            return '<span class="source-badge source-' + data + '">' + 
                                   data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    { 
                        data: 'intent',
                        render: function(data) {
                            return '<span class="intent-badge intent-' + data + '">' + 
                                   data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                        }
                    },
                    { 
                        data: 'clicks',
                        render: $.fn.dataTable.render.number(',', '.', 0, '')
                    },
                    { 
                        data: 'impressions',
                        render: $.fn.dataTable.render.number(',', '.', 0, '')
                    },
                    { 
                        data: 'ctr',
                        render: function(data) {
                            return data.toFixed(2) + '%';
                        }
                    },
                    { 
                        data: 'cost',
                        render: function(data) {
                            return SL_AI_PRO.currency + ' ' + data.toFixed(2);
                        }
                    },
                    { 
                        data: 'conversion_rate',
                        render: function(data) {
                            return data.toFixed(2) + '%';
                        }
                    },
                    { 
                        data: 'score',
                        render: function(data) {
                            return '<div class="score-bar">' +
                                   '<div class="score-fill" style="width: ' + data + '%;"></div>' +
                                   '<span class="score-text">' + data + '%</span>' +
                                   '</div>';
                        }
                    },
                    { 
                        data: 'actions',
                        render: function(data, type, row) {
                            return '<div class="action-buttons">' +
                                   '<button class="button button-small action-analyze" data-id="' + row.id + '">' +
                                   '<span class="dashicons dashicons-chart-pie"></span></button>' +
                                   '<button class="button button-small action-edit" data-id="' + row.id + '">' +
                                   '<span class="dashicons dashicons-edit"></span></button>' +
                                   '<button class="button button-small action-delete" data-id="' + row.id + '">' +
                                   '<span class="dashicons dashicons-trash"></span></button>' +
                                   '</div>';
                        }
                    }
                ],
                order: [[3, 'desc']],
                pageLength: 25,
                responsive: true,
                dom: '<"top"flp>rt<"bottom"ip><"clear">',
                language: {
                    search: SL_AI_PRO.i18n.search + ':',
                    lengthMenu: SL_AI_PRO.i18n.rows_per_page + ' _MENU_',
                    info: SL_AI_PRO.i18n.showing + ' _START_ ' + SL_AI_PRO.i18n.to + ' _END_ ' + SL_AI_PRO.i18n.of + ' _TOTAL_ ' + SL_AI_PRO.i18n.entries,
                    paginate: {
                        first: '«',
                        last: '»',
                        next: '›',
                        previous: '‹'
                    }
                }
            });
            
            // Bind action buttons
            $('#keywords-table').on('click', '.action-analyze', (e) => {
                this.analyzeKeyword($(e.currentTarget).data('id'));
            });
            
            $('#keywords-table').on('click', '.action-edit', (e) => {
                this.editKeyword($(e.currentTarget).data('id'));
            });
            
            $('#keywords-table').on('click', '.action-delete', (e) => {
                this.deleteKeyword($(e.currentTarget).data('id'));
            });
        }
        
        loadDashboardData(type = 'all') {
            this.showLoading();
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_refresh_dashboard',
                    data_type: type,
                    date_range: this.getCurrentDateRange(),
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateDashboard(response.data);
                    } else {
                        this.showError(response.data?.message || SL_AI_PRO.i18n.error);
                    }
                },
                error: (xhr, status, error) => {
                    this.showError(error || SL_AI_PRO.i18n.error);
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        updateDashboard(data) {
            // Update quick stats
            if (data.stats) {
                this.updateQuickStats(data.stats);
            }
            
            // Update charts
            if (data.charts) {
                this.updateCharts(data.charts);
            }
            
            // Update tables
            if (data.keywords) {
                this.updateKeywordsTable(data.keywords);
            }
            
            if (data.pages) {
                this.updatePagesTable(data.pages);
            }
            
            // Update timestamp
            if (data.timestamp) {
                this.updateTimestamp(data.timestamp);
            }
            
            // Update alerts
            if (data.alerts) {
                this.updateAlertsBadge(data.alerts.length);
            }
        }
        
        updateQuickStats(stats) {
            // Ads stats
            $('#sl-ai-pro-stats-ads-clicks').text(stats.ads.clicks.toLocaleString());
            $('#sl-ai-pro-stats-ads-change').text(stats.ads.change.value + '%')
                .removeClass('positive negative neutral')
                .addClass(stats.ads.change.class);
            
            // SEO stats
            $('#sl-ai-pro-stats-seo-clicks').text(stats.seo.clicks.toLocaleString());
            $('#sl-ai-pro-stats-seo-change').text(stats.seo.change.value + '%')
                .removeClass('positive negative neutral')
                .addClass(stats.seo.change.class);
            
            // Conversions stats
            $('#sl-ai-pro-stats-conversions-count').text(stats.conversions.count.toLocaleString());
            $('#sl-ai-pro-stats-conversions-change').text(stats.conversions.change.value + '%')
                .removeClass('positive negative neutral')
                .addClass(stats.conversions.change.class);
            
            // Intent stats
            $('#sl-ai-pro-stats-intent-commercial').text(stats.intent.commercial.toFixed(1) + '%');
            $('#sl-ai-pro-stats-intent-change').text(stats.intent.change.value + '%')
                .removeClass('positive negative neutral')
                .addClass(stats.intent.change.class);
        }
        
        updateCharts(chartData) {
            // Overview chart
            if (charts.overview && chartData.dates) {
                charts.overview.updateOptions({
                    xaxis: { categories: chartData.dates }
                });
                
                charts.overview.updateSeries([
                    { name: 'Ads Clicks', data: chartData.ads },
                    { name: 'SEO Clicks', data: chartData.seo },
                    { name: 'Intent Score', data: chartData.intent }
                ]);
            }
        }
        
        updateKeywordsTable(keywords) {
            if (dataTables.keywords) {
                dataTables.keywords.clear();
                dataTables.keywords.rows.add(keywords);
                dataTables.keywords.draw();
            }
        }
        
        updatePagesTable(pages) {
            // Implementation depends on how pages table is structured
        }
        
        updateTimestamp(timestamp) {
            const timeEl = $('#sl-ai-pro-last-updated');
            if (timeEl.length) {
                const date = new Date(timestamp);
                timeEl.text(date.toLocaleTimeString());
            }
        }
        
        updateAlertsBadge(count) {
            $('.alert-count').text(count).toggle(count > 0);
        }
        
        loadPerformanceChart() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'performance_chart',
                    params: {
                        metric: $('#performance-metric').val(),
                        breakdown: $('#performance-breakdown').val(),
                        comparison: $('#performance-comparison').val(),
                        date_range: this.getCurrentDateRange()
                    },
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updatePerformanceChart(response.data);
                    }
                }
            });
        }
        
        updatePerformanceChart(data) {
            if (charts.performance && data.series && data.categories) {
                charts.performance.updateOptions({
                    xaxis: { categories: data.categories },
                    yaxis: { title: { text: data.yAxisTitle } }
                });
                
                charts.performance.updateSeries(data.series);
                
                // Update summary and insights
                $('#performance-summary-content').html(data.summary || '');
                $('#performance-insights-content').html(data.insights || '');
            }
        }
        
        loadIntentChart() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'intent_distribution',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success && charts.intent) {
                        charts.intent.updateSeries([{ data: response.data.data }]);
                        charts.intent.updateOptions({ labels: response.data.labels });
                        
                        // Update summary
                        $('#intent-summary-content').html(
                            '<p>Total analyzed: ' + response.data.total + ' queries</p>' +
                            '<div class="intent-breakdown">' +
                            response.data.labels.map((label, index) => 
                                '<div class="intent-item">' +
                                '<span class="intent-color" style="background-color: ' + response.data.colors[index] + '"></span>' +
                                '<span class="intent-label">' + label + ': ' + response.data.data[index] + '%</span>' +
                                '</div>'
                            ).join('') +
                            '</div>'
                        );
                    }
                }
            });
        }
        
        loadIntentPatterns() {
            // Load commercial patterns
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'commercial_patterns',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#commercial-patterns').html(response.data.html || 'No patterns found');
                    }
                }
            });
            
            // Load informational patterns
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'informational_patterns',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#informational-patterns').html(response.data.html || 'No patterns found');
                    }
                }
            });
        }
        
        loadRecommendations() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'recommendations_list',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#recommendations-list').html(response.data.html || 'No recommendations');
                        this.updateRecommendationStats(response.data.stats);
                    }
                }
            });
        }
        
        updateRecommendationStats(stats) {
            $('#total-recommendations').text(stats.total || 0);
            $('#high-priority').text(stats.high_priority || 0);
            $('#estimated-impact').text(stats.estimated_impact || '$0');
            $('#applied-recommendations').text(stats.applied || 0);
        }
        
        loadAlerts() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_get_widget_data',
                    widget: 'alerts_list',
                    params: {
                        filters: this.getAlertFilters()
                    },
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#alerts-list').html(response.data.html || 'No alerts');
                    }
                }
            });
        }
        
        getAlertFilters() {
            return {
                type: $('#alerts-type').val() || [],
                severity: $('#alerts-severity').val() || [],
                status: $('#alerts-status').val() || [],
                date_range: $('#alerts-date').val()
            };
        }
        
        refreshDashboard(source = 'manual') {
            console.log('Refreshing dashboard from:', source);
            this.loadDashboardData();
        }
        
        exportDashboard() {
            const format = prompt(SL_AI_PRO.i18n.export + ' format (csv, excel, pdf, json):', 'csv');
            if (!format) return;
            
            const dataType = prompt('Export data type (all, keywords, performance, recommendations):', 'all');
            if (!dataType) return;
            
            window.location.href = SL_AI_PRO.ajax_url + 
                '?action=sl_ai_pro_export_dashboard&format=' + format + 
                '&data_type=' + dataType + 
                '&date_range=' + this.getCurrentDateRange() + 
                '&nonce=' + SL_AI_PRO.nonce;
        }
        
        handleDateRangeChange(range) {
            if (range === 'custom') {
                $('.custom-date-range').show();
            } else {
                $('.custom-date-range').hide();
                this.loadDashboardData();
            }
        }
        
        applyCustomDateRange() {
            const from = $('#sl-ai-pro-date-from').val();
            const to = $('#sl-ai-pro-date-to').val();
            
            if (!from || !to) {
                alert('Please select both dates');
                return;
            }
            
            // Store custom range and refresh
            localStorage.setItem('sl_ai_pro_custom_range', JSON.stringify({ from, to }));
            this.loadDashboardData();
        }
        
        getCurrentDateRange() {
            const range = $('#sl-ai-pro-date-range').val();
            if (range === 'custom') {
                const custom = JSON.parse(localStorage.getItem('sl_ai_pro_custom_range') || '{}');
                return custom.from && custom.to ? 'custom' : 'last_7_days';
            }
            return range;
        }
        
        runAnalysis() {
            this.showLoading();
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_run_analysis',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 'Analysis completed successfully');
                        this.refreshDashboard('analysis');
                    } else {
                        this.showError(response.data?.message || 'Analysis failed');
                    }
                },
                error: () => {
                    this.showError('Analysis failed');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        generateReport() {
            // Open report generation modal or start process
            this.showMessage('Info', 'Report generation started. You will be notified when ready.');
        }
        
        findKeywords() {
            $('#keywords-import-modal').show();
        }
        
        optimizeNegatives() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_optimize_negatives',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.openAnalysisResultsModal(response.data);
                    }
                }
            });
        }
        
        analyzePages() {
            // Implementation for page analysis
        }
        
        changeChartType(button) {
            $('.chart-type-btn').removeClass('active');
            button.addClass('active');
            
            const type = button.data('type');
            if (charts.overview) {
                charts.overview.updateOptions({ chart: { type: type } });
            }
        }
        
        openKeywordsImportModal() {
            $('#keywords-import-modal').show();
        }
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
                    alert('Please select a CSV file');
                    event.target.value = '';
                    return;
                }
                
                $('#keywords-import-start').prop('disabled', false);
                $('.import-options').show();
            }
        }
        
        startKeywordsImport() {
            const fileInput = $('#keywords-csv-file')[0];
            if (!fileInput.files.length) {
                alert('Please select a file first');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('action', 'sl_ai_pro_import_keywords');
            formData.append('nonce', SL_AI_PRO.nonce);
            formData.append('run_analysis', $('#import-run-analysis').is(':checked'));
            formData.append('check_duplicates', $('#import-check-duplicates').is(':checked'));
            formData.append('add_to_campaigns', $('#import-add-to-campaigns').is(':checked'));
            
            this.showLoading();
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 
                            'Imported ' + response.data.count + ' keywords successfully');
                        this.closeModal('#keywords-import-modal');
                        this.refreshDashboard('import');
                    } else {
                        this.showError(response.data?.message || 'Import failed');
                    }
                },
                error: () => {
                    this.showError('Import failed');
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        }
        
        applyPerformanceFilters() {
            this.loadPerformanceChart();
            if (dataTables.performance) {
                dataTables.performance.ajax.reload();
            }
        }
        
        applyKeywordsFilters() {
            const filters = {
                search: $('#keywords-search').val(),
                source: $('#keywords-source').val() || [],
                intent: $('#keywords-intent').val() || [],
                status: $('#keywords-status').val() || [],
                score: $('#keywords-score').val()
            };
            
            if (dataTables.keywords) {
                dataTables.keywords.ajax.reload();
            }
        }
        
        resetKeywordsFilters() {
            $('#keywords-search').val('');
            $('#keywords-source').val([]).trigger('change');
            $('#keywords-intent').val([]).trigger('change');
            $('#keywords-status').val([]).trigger('change');
            $('#keywords-score').val('');
            
            this.applyKeywordsFilters();
        }
        
        analyzeIntent() {
            const query = $('#intent-query').val().trim();
            const language = $('#intent-language').val();
            
            if (!query) {
                alert('Please enter a query to analyze');
                return;
            }
            
            $('#intent-results-content').html('<p>Analyzing...</p>');
            $('#intent-results').show();
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_analyze_intent',
                    query: query,
                    language: language,
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayIntentResults(response.data);
                    } else {
                        $('#intent-results-content').html(
                            '<p class="error">' + (response.data?.message || 'Analysis failed') + '</p>'
                        );
                    }
                },
                error: () => {
                    $('#intent-results-content').html('<p class="error">Analysis failed</p>');
                }
            });
        }
        
        displayIntentResults(data) {
            let html = '<div class="intent-analysis-results">';
            
            // Primary intent
            html += '<div class="primary-intent">';
            html += '<h6>Primary Intent: ' + data.primary + '</h6>';
            html += '<div class="confidence">Confidence: ' + data.confidence.toFixed(1) + '%</div>';
            html += '</div>';
            
            // Intent scores
            html += '<div class="intent-scores">';
            html += '<h6>Intent Scores:</h6>';
            html += '<div class="score-bars">';
            
            for (const [intent, score] of Object.entries(data.scores)) {
                html += '<div class="score-item">';
                html += '<div class="score-label">' + intent + '</div>';
                html += '<div class="score-bar-container">';
                html += '<div class="score-bar-fill" style="width: ' + score + '%;"></div>';
                html += '<div class="score-value">' + score.toFixed(1) + '%</div>';
                html += '</div>';
                html += '</div>';
            }
            
            html += '</div></div>';
            
            // Suggestions
            if (data.suggestions) {
                html += '<div class="intent-suggestions">';
                html += '<h6>Recommendations:</h6>';
                html += '<ul>';
                for (const [key, value] of Object.entries(data.suggestions)) {
                    html += '<li><strong>' + key + ':</strong> ' + value + '</li>';
                }
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#intent-results-content').html(html);
        }
        
        copyIntentResults() {
            const text = $('#intent-results-content').text();
            navigator.clipboard.writeText(text).then(() => {
                this.showMessage('Success', 'Results copied to clipboard');
            });
        }
        
        analyzeIntentBulk() {
            // Open bulk analysis modal
            this.showMessage('Info', 'Bulk intent analysis feature coming soon');
        }
        
        analyzeKeyword(keywordId) {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_analyze_keyword',
                    keyword_id: keywordId,
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.openAnalysisResultsModal(response.data);
                    }
                }
            });
        }
        
        editKeyword(keywordId) {
            // Open edit modal
            console.log('Edit keyword:', keywordId);
        }
        
        deleteKeyword(keywordId) {
            if (!confirm(SL_AI_PRO.i18n.confirm + ' This will delete the keyword and all associated data.')) {
                return;
            }
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_delete_keyword',
                    keyword_id: keywordId,
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 'Keyword deleted');
                        if (dataTables.keywords) {
                            dataTables.keywords.ajax.reload();
                        }
                    }
                }
            });
        }
        
        generateRecommendations() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_generate_recommendations',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 
                            'Generated ' + response.data.count + ' new recommendations');
                        this.loadRecommendations();
                    }
                }
            });
        }
        
        applyAllRecommendations() {
            if (!confirm('Apply all recommendations? This may make changes to your campaigns.')) {
                return;
            }
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_apply_all_recommendations',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 
                            'Applied ' + response.data.applied + ' recommendations');
                        this.loadRecommendations();
                    }
                }
            });
        }
        
        markAllAlertsRead() {
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_mark_all_alerts_read',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 'All alerts marked as read');
                        this.loadAlerts();
                        this.updateAlertsBadge(0);
                    }
                }
            });
        }
        
        clearAllAlerts() {
            if (!confirm('Clear all alerts? This cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_clear_all_alerts',
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 'All alerts cleared');
                        this.loadAlerts();
                        this.updateAlertsBadge(0);
                    }
                }
            });
        }
        
        openAlertSettings() {
            $('#alert-settings-modal').show();
        }
        
        saveAlertSettings() {
            const formData = $('.alert-settings-form').serializeArray();
            const data = {};
            
            $.each(formData, function() {
                data[this.name] = this.value;
            });
            
            $.ajax({
                url: SL_AI_PRO.ajax_url,
                type: 'POST',
                data: {
                    action: 'sl_ai_pro_save_alert_settings',
                    settings: data,
                    nonce: SL_AI_PRO.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage(SL_AI_PRO.i18n.success, 'Alert settings saved');
                        this.closeModal('#alert-settings-modal');
                    }
                }
            });
        }
        
        openAnalysisResultsModal(data) {
            $('#analysis-results-content').html(this.formatAnalysisResults(data));
            $('#analysis-results-modal').show();
        }
        
        formatAnalysisResults(data) {
            let html = '<div class="analysis-results">';
            
            if (data.summary) {
                html += '<div class="results-summary">' + data.summary + '</div>';
            }
            
            if (data.recommendations && data.recommendations.length) {
                html += '<div class="results-recommendations">';
                html += '<h4>Recommendations</h4>';
                html += '<ul>';
                data.recommendations.forEach(rec => {
                    html += '<li>' + rec + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            if (data.keywords && data.keywords.length) {
                html += '<div class="results-keywords">';
                html += '<h4>Keywords to Add</h4>';
                html += '<ul>';
                data.keywords.forEach(kw => {
                    html += '<li><strong>' + kw.keyword + '</strong> - ' + kw.match_type + 
                           ' (Score: ' + kw.score + '%)</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            if (data.negatives && data.negatives.length) {
                html += '<div class="results-negatives">';
                html += '<h4>Negative Keywords</h4>';
                html += '<ul>';
                data.negatives.forEach(neg => {
                    html += '<li><strong>' + neg.keyword + '</strong> - ' + neg.match_type + 
                           ' (Est. Savings: ' + neg.estimated_savings + ')</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            return html;
        }
        
        toggleWidget(widget) {
            widget.toggleClass('collapsed');
            const widgetId = widget.attr('id');
            const isCollapsed = widget.hasClass('collapsed');
            
            // Save state
            this.saveWidgetState(widgetId, 'collapsed', isCollapsed);
            
            // Update icon
            const toggleIcon = widget.find('.widget-toggle .dashicons');
            toggleIcon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        }
        
        configureWidget(widget) {
            const widgetId = widget.attr('id');
            const config = this.getWidgetConfig(widgetId);
            
            // Open configuration modal for this widget
            this.showMessage('Widget Configuration', 'Configure widget: ' + widgetId);
        }
        
        saveWidgetState(widgetId, key, value) {
            let states = JSON.parse(localStorage.getItem('sl_ai_pro_widget_states') || '{}');
            if (!states[widgetId]) states[widgetId] = {};
            states[widgetId][key] = value;
            localStorage.setItem('sl_ai_pro_widget_states', JSON.stringify(states));
        }
        
        getWidgetConfig(widgetId) {
            const states = JSON.parse(localStorage.getItem('sl_ai_pro_widget_states') || '{}');
            return states[widgetId] || {};
        }
        
        initSortableWidgets() {
            $('.widget-container').sortable({
                handle: '.widget-header',
                placeholder: 'widget-placeholder',
                opacity: 0.7,
                update: function(event, ui) {
                    const widgetOrder = $(this).sortable('toArray', { attribute: 'data-widget-id' });
                    localStorage.setItem('sl_ai_pro_widget_order', JSON.stringify(widgetOrder));
                }
            });
        }
        
        initDarkMode() {
            if (SL_AI_PRO.user_preferences.dark_mode) {
                $('body').addClass('sl-ai-pro-dark-mode');
            }
        }
        
        startAutoRefresh() {
            if (SL_AI_PRO.user_preferences.auto_refresh && SL_AI_PRO.refresh_interval > 0) {
                clearInterval(refreshInterval);
                refreshInterval = setInterval(() => {
                    if (!document.hidden) {
                        this.refreshDashboard('auto');
                    }
                }, SL_AI_PRO.refresh_interval * 1000);
            }
        }
        
        showLoading() {
            $('#sl-ai-pro-loading').show();
        }
        
        hideLoading() {
            $('#sl-ai-pro-loading').hide();
        }
        
        showMessage(title, message, type = 'info') {
            // Create or use existing notification system
            const notification = $('<div class="sl-ai-pro-notification ' + type + '"></div>')
                .html('<strong>' + title + '</strong><br>' + message)
                .appendTo('body');
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
        }
        
        showError(message) {
            this.showMessage(SL_AI_PRO.i18n.error, message, 'error');
        }
        
        closeModal(modalSelector) {
            $(modalSelector).hide();
        }
    }
    
    // Initialize dashboard when DOM is ready
    $(document).ready(function() {
        if (typeof ApexCharts !== 'undefined') {
            window.sl_ai_pro_dashboard_instance = new Dashboard();
        } else {
            console.error('ApexCharts not loaded');
        }
    });
    
})(jQuery);