<?php
namespace SosyalliftAIPro\Core\Intelligence;

defined('ABSPATH') || exit;

class IntelligenceResult {

    public function __construct(
        public int $anomaly_score,
        public array $explanation,
        public int $confidence
    ) {}

    public function toArray(): array {
        return [
            'anomaly_score' => $this->anomaly_score,
            'explanation'   => $this->explanation,
            'confidence'    => $this->confidence,
        ];
    }
}
