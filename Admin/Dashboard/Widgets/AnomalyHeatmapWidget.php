<?php
namespace SosyalliftAIPro\Admin\Dashboard\Widgets;

class AnomalyHeatmapWidget {

    public static function render(): void {

        $ads = get_option('sl_ai_pro_ads_anomaly_score', []);
        $seo = get_option('sl_ai_pro_serp_anomaly', []);

        echo '<div class="sl-widget">';
        echo '<h3>Anomaly Heatmap</h3>';

        echo '<table class="widefat">';
        echo '<tr><th>Source</th><th>Score</th></tr>';

        foreach (['ADS' => $ads, 'SEO' => $seo] as $type => $score) {
            echo '<tr>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '<td>' . esc_html($score ?: 'normal') . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '</div>';
    }
}
