setInterval(() => {
    jQuery.post(ajaxurl, {
        action: 'sl_ai_pro_refresh_dashboard',
        nonce: SL_AI_PRO.nonce
    }, (html) => {
        jQuery('.sl-ai-pro-dashboard').html(html);
    });
}, 10000);
