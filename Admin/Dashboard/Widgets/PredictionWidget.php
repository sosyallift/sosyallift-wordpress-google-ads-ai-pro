<?php
namespace SosyalliftAIPro\Admin\Dashboard\Widgets;

use SosyalliftAIPro\Core\Prediction\PredictionEngine;

class PredictionWidget {

    public static function render(): void {

        $result = PredictionEngine::run()->to_array();

        echo '<div class="sl-widget">';
        echo '<h3>AI Prediction Status</h3>';
        echo '<pre>' . esc_html(json_encode($result, JSON_PRETTY_PRINT)) . '</pre>';
        echo '</div>';
    }
}
