<?php
namespace SosyalliftAIPro\Core\Anomaly\Contracts;

interface MetricInterface {
    public static function extract(array $payload): array;
}
