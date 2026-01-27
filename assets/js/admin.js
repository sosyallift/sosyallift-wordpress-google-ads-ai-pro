// Sosyallift AI Pro Admin JS
(function($) {
    'use strict';
    const SL_AI = {
        init: function() {
            this.bindEvents();
            this.initCharts();
        },
        bindEvents: function() {
            // Refresh stats
            $(document).on('click', '#refresh-stats', function(e) {
                e.preventDefault();
                SL_AI.loadStats();
            });
            // Run analysis
            $(document).on('click', '#run-analysis', function(e) {
                e.preventDefault();
                SL_AI.runAnalysis();
            });
            // Export data
            $(document).on('click', '#export-data', function(e) {
                e.preventDefault();
                SL_AI.exportData();
            });
        },
        loadStats: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sl_ai_get_stats',
                    nonce: sl_ai_ajax.nonce
                },
                beforeSend: function() {
                    $('.stat-value').text('...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#total-clicks').text(response.data.total_clicks);
                        $('#conversions').text(response.data.conversions);
                        $('#ctr').text(response.data.ctr + '%');
                        $('#roas').text(response.data.roas);
                    }
                },
                error: function() {
                    alert('Veri yüklenirken hata oluştu.');
                }
            });
        },
        runAnalysis: function() {
            if (!confirm('Analiz çalıştırılsın mı?')) return;
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'sl_ai_run_analysis',
                    nonce: sl_ai_ajax.nonce
                },
                beforeSend: function() {
                    $('#run-analysis').prop('disabled', true).text('Analiz Çalışıyor...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Analiz tamamlandı!');
                        SL_AI.loadStats();
                    }
                },
                complete: function() {
                    $('#run-analysis').prop('disabled', false).text('Analiz Çalıştır');
                }
            });
        },
        exportData: function() {
            window.location.href = ajaxurl + '?action=sl_ai_export_data&nonce=' + sl_ai_ajax.nonce;
        },
        initCharts: function() {
            // Chart.js initialization
            if (typeof Chart !== 'undefined') {
                const ctx = document.getElementById('chart-container');
                if (ctx) {
                    new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs'],
                            datasets: [{
                                label: 'Tıklamalar',
                                data: [1200, 1900, 1500, 2100, 1800],
                                borderColor: '#2271b1',
                                tension: 0.1
                            }]
                        }
                    });
                }
            }
        }
    };
    // Document ready
    $(document).ready(function() {
        SL_AI.init();
    });
})(jQuery);
