<div class="wrap">
    <h1>AI Intelligence Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields('sl_ai_settings'); ?>
        <h2>Genel Ayarlar</h2>
        <table class="form-table">
            <tr>
                <th><label for="currency">Para Birimi</label></th>
                <td>
                    <select name="sl_ai_currency" id="currency">
                        <option value="TRY" <?php selected(get_option('sl_ai_currency'), 'TRY'); ?>>TRY</option>
                        <option value="USD" <?php selected(get_option('sl_ai_currency'), 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected(get_option('sl_ai_currency'), 'EUR'); ?>>EUR</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="auto_sync">Otomatik Senkronizasyon</label></th>
                <td>
                    <input type="checkbox" name="sl_ai_auto_sync" id="auto_sync" 
                           value="1" <?php checked(get_option('sl_ai_auto_sync'), 1); ?>>
                    <label for="auto_sync">Her 5 dakikada bir veri çek</label>
                </td>
            </tr>
            <tr>
                <th><label for="cache_ttl">Cache Süresi (saniye)</label></th>
                <td>
                    <input type="number" name="sl_ai_cache_ttl" id="cache_ttl" 
                           value="<?php echo esc_attr(get_option('sl_ai_cache_ttl', 300)); ?>" 
                           min="60" max="3600">
                </td>
            </tr>
        </table>
        <h2>Google Ads Ayarları</h2>
        <table class="form-table">
            <tr>
                <th><label for="dev_token">Developer Token</label></th>
                <td>
                    <input type="password" name="sl_ai_google_dev_token" id="dev_token" 
                           value="<?php echo esc_attr(get_option('sl_ai_google_dev_token')); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="client_id">Client ID</label></th>
                <td>
                    <input type="text" name="sl_ai_google_client_id" id="client_id" 
                           value="<?php echo esc_attr(get_option('sl_ai_google_client_id')); ?>"
                           class="regular-text">
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
