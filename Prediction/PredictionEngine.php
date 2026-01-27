<?php
namespace SosyalliftAIPro\Core\Prediction;

use SosyalliftAIPro\Core\Logs\Logger;
use SosyalliftAIPro\Core\Logs\LogTypes;

class PredictionEngine {

    public static function run(): PredictionResult {

        $context = PredictionContext::build();
        $result  = new PredictionResult();

        // 🔌 AI / MODEL YOK → FALLBACK
        $result->status = 'noop';
        $result->insights[] = 'Prediction engine ready, model not attached';

        Logger::get_instance()->log(
            LogTypes::PREDICTION,
            'PredictionEngine',
            'Prediction executed (noop)',
            ['context' => $context]
        );
		
			$result = apply_filters(
				'sl_ai_pro_prediction_engine',
				$result,
				$context
			);

        return $result;
    }
}
