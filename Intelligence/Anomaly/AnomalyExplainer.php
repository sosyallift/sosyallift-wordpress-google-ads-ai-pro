<?php
namespace SosyalliftAIPro\Core\Intelligence\Anomaly;

use SosyalliftAIPro\Core\Intelligence\IntelligenceContext;

defined('ABSPATH') || exit;

class AnomalyExplainer {

    public static function explain(
        IntelligenceContext $context,
        int $score
    ): array {

        $explanations = [];
        $labels       = [];
        $action       = 'observe';

        /**
         * Yüksek skor → kritik durum
         */
        if ($score >= 70) {
            $labels[] = 'critical';
            $action  = 'intervene';
        } elseif ($score >= 40) {
            $labels[] = 'warning';
            $action  = 'analyze';
        } else {
            $labels[] = 'stable';
        }

        /**
         * Intent tabanlı yorum
         */
        if (!empty($context->intent['commercial'])) {

            if (($context->ads['clicks'] ?? 0) === 0) {
                $explanations[] =
                    'Ticari niyet algılandı ancak reklam trafiği yok. Fırsat kaçıyor olabilir.';
                $labels[] = 'missed-opportunity';
            }

            if (
                ($context->ads['clicks'] ?? 0) > 0 &&
                ($context->traffic['visitors'] ?? 0) === 0
            ) {
                $explanations[] =
                    'Reklam tıklaması var ancak site trafiği oluşmuyor. Trafik kaybı veya yönlendirme sorunu.';
                $labels[] = 'traffic-leak';
                $action  = 'pause-or-fix';
            }
        }

        /**
         * SEO etkisi
         */
        if (($context->seo['health'] ?? '') !== 'ok') {
            $explanations[] =
                'SEO sağlığı bozuk. Organik trafik anomalilere katkı sağlıyor olabilir.';
            $labels[] = 'seo-impact';
        }

        return [
            'labels'       => array_values(array_unique($labels)),
            'summary'      => $explanations ?: ['Belirgin ticari risk tespit edilmedi.'],
            'action_hint'  => $action,
        ];
    }
}
