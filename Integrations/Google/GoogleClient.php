<?php
namespace SosyalliftAIPro\Integrations\Google;

use Exception;

class GoogleClient {

    protected $oauth;
    protected $tokens;

    public function __construct(
        OAuthManager $oauth,
        TokenRepository $tokens
    ) {
        $this->oauth  = $oauth;
        $this->tokens = $tokens;
    }

    public function request(string $endpoint, array $params = []) {

        $accessToken = $this->tokens->getAccessToken();

        if (!$accessToken) {
            throw new Exception('Google access token not found');
        }

        $url = $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
