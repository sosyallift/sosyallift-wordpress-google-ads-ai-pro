<?php
namespace SosyalliftAIPro\Admin\Dashboard;

use SosyalliftAIPro\Admin\Dashboard\Widgets\PredictionWidget;
use SosyalliftAIPro\Admin\Dashboard\Widgets\AnomalyHeatmapWidget;
use SosyalliftAIPro\Admin\Dashboard\Widgets\AgentActionsWidget;

class DashboardBuilder {

    public static function render(): void {
        echo '<div class="sl-ai-pro-dashboard">';

        PredictionWidget::render();
        AnomalyHeatmapWidget::render();
        AgentActionsWidget::render();

        echo '</div>';
    }
}
