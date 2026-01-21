<?php
namespace SosyalliftAIPro\Includes\Interfaces;

interface AnalyzerInterface {
    public function analyze($data): array;
    public function analyze_bulk(array $data, array $options = []): array;
    public function get_confidence_score(): float;
    public function get_supported_types(): array;
    public function validate_input($data): bool;
}