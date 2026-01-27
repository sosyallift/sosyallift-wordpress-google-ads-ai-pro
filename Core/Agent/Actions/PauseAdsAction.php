<?php
namespace SosyalliftAIPro\Core\Agent\Actions;

class PauseAdsAction {

    public static function handle($prediction): void {
        if (in_array('high_ads_anomaly', $prediction->insights)) {
            // Google Ads API pause logic
        }
    }
}
