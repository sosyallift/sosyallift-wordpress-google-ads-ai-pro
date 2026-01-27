(function ($) {

    function fetchStats() {
        $.post(
            ajaxurl,
            {
                action: 'sl_ai_pro_dashboard_stats',
                nonce: SL_AI_PRO.nonce
            },
            function (response) {

                if (!response.success) {
                    return;
                }

                const d = response.data;

                $('#visitors').text(d.visitors);
                $('#ads_clicks').text(d.ads_clicks);
                $('#conversions').text(d.conversions);
                $('#timestamp').text(d.timestamp);
            }
        );
    }

    fetchStats();
    setInterval(fetchStats, 10000); // 10 saniye

})(jQuery);
