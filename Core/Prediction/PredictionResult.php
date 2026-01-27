<?php
namespace SosyalliftAIPro\Core\Prediction;

class PredictionResult {

    public string $status = 'noop'; // noop | predicted | anomaly
    public array $insights = [];
    public array $recommendations = [];

    public function to_array(): array {
        return [
            'status' => $this->status,
            'insights' => $this->insights,
            'recommendations' => $this->recommendations,
        ];
    }
}
