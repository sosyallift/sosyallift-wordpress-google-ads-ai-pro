<div class="sl-ai-cards">
    <div class="card">Ads Score: <strong><?= esc_html($initial['scores']['ads'] ?? '-') ?></strong></div>
    <div class="card">SEO Score: <strong><?= esc_html($initial['scores']['seo'] ?? '-') ?></strong></div>
    <div class="card">Behavior Risk: <strong><?= esc_html($initial['scores']['behavior'] ?? '-') ?></strong></div>
    <div class="card">Anomalies: <strong><?= count($initial['anomalies'] ?? []) ?></strong></div>
</div>
