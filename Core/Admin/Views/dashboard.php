<?php defined('ABSPATH') || exit; ?>

<div class="wrap">
    <h1>Sosyallift AI Pro – Dashboard</h1>

    <h2>Genel Durum</h2>
    <table class="widefat striped">
        <tbody>
        <tr><th>Toplam Anahtar Kelime</th><td><?= esc_html($data['stats']['keywords']) ?></td></tr>
        <tr><th>Toplam Log</th><td><?= esc_html($data['stats']['logs']) ?></td></tr>
        <tr><th>Intent Kaydı</th><td><?= esc_html($data['stats']['intents']) ?></td></tr>
        </tbody>
    </table>

    <h2>Son Uyarılar</h2>
    <table class="widefat striped">
        <thead>
        <tr><th>Tarih</th><th>Seviye</th><th>Mesaj</th></tr>
        </thead>
        <tbody>
        <?php foreach ($data['alerts'] as $alert): ?>
            <tr>
                <td><?= esc_html($alert['created_at']) ?></td>
                <td><?= esc_html(strtoupper($alert['level'])) ?></td>
                <td><?= esc_html($alert['message']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
	
	<div class="wrap">
    <h1>Sosyallift AI Pro – Dashboard</h1>

    <table class="widefat striped" id="sl-ai-pro-stats">
        <thead>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Visitors</td><td id="visitors">–</td></tr>
            <tr><td>Ads Clicks</td><td id="ads_clicks">–</td></tr>
            <tr><td>Conversions</td><td id="conversions">–</td></tr>
            <tr><td>Last Update</td><td id="timestamp">–</td></tr>
        </tbody>
    </table>

    <p><em>Live data – reload yok</em></p>
</div>

</div>
