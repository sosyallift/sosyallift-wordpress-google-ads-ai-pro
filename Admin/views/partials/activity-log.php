<h2>Agent Activity</h2>
<ul id="sl-ai-activity-log">
    <?php foreach (($initial['activity'] ?? []) as $log): ?>
        <li><?= esc_html($log['message']) ?></li>
    <?php endforeach; ?>
</ul>
