<?php
namespace SosyalliftAIPro\Core\AI;

final class AIResponse {

    private array $data;

    public function __construct(array $raw) {
        $this->data = $raw;
    }

    public function getRecommendations(): array {
        return $this->data['recommendations'] ?? [];
    }

    public function getScores(): array {
        return $this->data['scores'] ?? [];
    }

    public function getMeta(): array {
        return $this->data['meta'] ?? [];
    }
}
