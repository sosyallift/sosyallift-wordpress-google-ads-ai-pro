<div class="wrap sl-ai-dashboard">
    <h1>AI Intelligence Dashboard</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Toplam Tıklama</h3>
            <div class="stat-value" id="total-clicks">0</div>
        </div>
        <div class="stat-card">
            <h3>Dönüşüm</h3>
            <div class="stat-value" id="conversions">0</div>
        </div>
        <div class="stat-card">
            <h3>CTR</h3>
            <div class="stat-value" id="ctr">0%</div>
        </div>
        <div class="stat-card">
            <h3>ROAS</h3>
            <div class="stat-value" id="roas">0</div>
        </div>
    </div>
    <div class="dashboard-actions">
        <button class="button button-primary" id="refresh-stats">
            Verileri Yenile
        </button>
        <button class="button button-secondary" id="run-analysis">
            Analiz Çalıştır
        </button>
        <button class="button button-secondary" id="export-data">
            Veri Export
        </button>
    </div>
    <div id="chart-container" style="height: 400px; margin: 20px 0;">
        <!-- Chart buraya gelecek -->
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    // Stats yükle
    function loadStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sl_ai_get_stats',
                nonce: '<?php echo wp_create_nonce("sl_ai_ajax_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#total-clicks').text(response.data.total_clicks);
                    $('#conversions').text(response.data.conversions);
                    $('#ctr').text(response.data.ctr + '%');
                    $('#roas').text(response.data.roas);
                }
            }
        });
    }
    // Buton event'leri
    $('#refresh-stats').click(loadStats);
    $('#run-analysis').click(function() {
        $.post(ajaxurl, {
            action: 'sl_ai_run_analysis',
            nonce: '<?php echo wp_create_nonce("sl_ai_ajax_nonce"); ?>'
        }, function(response) {
            if (response.success) {
                alert('Analiz tamamlandı!');
                loadStats();
            }
        });
    });
    // İlk yükleme
    loadStats();
});
</script>
