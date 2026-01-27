<?php
namespace SosyalliftAIPro\Core\Anomaly;

defined('ABSPATH') || exit;

class AnomalyResult {

    protected array $flags = [];
    protected int $score = 0;

    public function add(string $type, string $message, int $weight = 1, array $context = []): void {
        $this->flags[] = [
            'type' => $type,
            'message' => $message,
            'context' => $context
        ];
        $this->score += $weight;
    }

    public function finalize(): self {
        return $this;
    }

    public function toArray(): array {
        return [
            'score' => $this->score,
            'flags' => $this->flags,
            'has_anomaly' => $this->score > 0
        ];
    }
}
