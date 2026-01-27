<?php
namespace SosyalliftAIPro\Core\Anomaly;

final class AnomalyResult {

    private array $signals = [];
    private array $scores  = [];
    private int $riskScore = 0;

    public function addSignal(string $domain, string $key, mixed $value): void {
        $this->signals[$domain][$key] = $value;
    }

    public function addScore(string $domain, int $score): void {
        $this->scores[$domain] = ($this->scores[$domain] ?? 0) + $score;
        $this->riskScore += $score;
    }

    public function finalize(): self {
        return $this;
    }

    public function toArray(): array {
        return [
            'risk_score' => $this->riskScore,
            'scores'     => $this->scores,
            'signals'    => $this->signals,
        ];
    }
}
