<?php
namespace SosyalliftAIPro\Core\AI;

final class AIRequest {

    private array $payload;

    public function __construct(array $context) {

        $this->payload = [
            'site' => [
                'url'       => home_url(),
                'blog_id'   => get_current_blog_id(),
                'env'       => wp_get_environment_type(),
            ],
            'timestamp' => time(),

            // domain data
            'seo'       => $context['seo']       ?? [],
            'ads'       => $context['ads']       ?? [],
            'behavior'  => $context['behavior']  ?? [],
            'anomalies' => $context['anomalies'] ?? [],

            'meta' => [
                'plugin_version' => defined('SL_AI_PRO_VERSION') ? SL_AI_PRO_VERSION : 'dev',
                'wp_version'     => get_bloginfo('version'),
            ],
        ];
    }

    public function toArray(): array {
        return $this->payload;
    }
}
