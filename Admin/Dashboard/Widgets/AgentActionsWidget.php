<?php
namespace SosyalliftAIPro\Admin\Dashboard\Widgets;

class AgentActionsWidget {

    public static function render(): void {

        echo '<div class="sl-widget">';
        echo '<h3>Agent Actions</h3>';
        echo '<ul>';
        echo '<li>Pause Ads on anomaly</li>';
        echo '<li>Flag negative keywords</li>';
        echo '<li>Notify admin</li>';
        echo '</ul>';
        echo '</div>';
    }
}
