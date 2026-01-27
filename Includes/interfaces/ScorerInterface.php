<?php
namespace SosyalliftAIPro\Includes\Interfaces;

interface ScorerInterface {
    public function calculate(array $data): float;
    public function get_score_range(): array;
    public function get_factors(): array;
    public function explain_score(float $score): string;
}