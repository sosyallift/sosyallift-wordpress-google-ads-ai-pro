<?php
namespace SosyalliftAIPro\Core\Intelligence;

defined('ABSPATH') || exit;

class IntelligenceContext {

    public array $traffic;
    public array $ads;
    public array $seo;
    public array $intent;

    public function __construct(array $payload) {
        $this->traffic = $payload['traffic'] ?? [];
        $this->ads     = $payload['ads'] ?? [];
        $this->seo     = $payload['seo'] ?? [];
        $this->intent  = $payload['intent'] ?? [];
    }
}
