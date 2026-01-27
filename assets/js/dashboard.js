(function ($) {

    function loadIntelligence() {
        $.post(
            SL_AI.ajax,
            {
                action: 'sl_ai_intelligence',
                _ajax_nonce: SL_AI.nonce
            },
            function (res) {

                if (!res.success) return;

                const d = res.data;

                $('#sl-score').text(d.anomaly_score + '/100');
                $('#sl-confidence').text(d.confidence + '%');
                $('#sl-status').text(
                    d.anomaly_score > 60 ? '⚠ Risk detected' : '✅ Normal'
                );

                $('#sl-explanation').empty();
                d.explanation.forEach(e => {
                    $('#sl-explanation').append('<li>' + e + '</li>');
                });
            }
        );
    }

    loadIntelligence();
    setInterval(loadIntelligence, 15000);

})(jQuery);
