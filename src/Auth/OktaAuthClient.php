<?php

namespace CustomizationPortal\Auth;

use Exception;

class OktaAuthClient
{
    private string $clientId;
    private string $issuer;
    private string $redirectUri;
    /** @var string[] */
    private array $scopes;

    /**
     * @param string[] $scopes
     */
    public function __construct(string $clientId, string $issuer, string $redirectUri, array $scopes)
    {
        $this->clientId = $clientId;
        $this->issuer = rtrim($issuer, '/');
        $this->redirectUri = $redirectUri;
        $this->scopes = $scopes;
    }

    public function buildAuthorizeUrl(string $state, string $codeChallenge, string $nonce): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
        ];

        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        return $this->issuer . '/v1/authorize?' . $query;
    }

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function exchangeCodeForTokens(string $code, string $codeVerifier): array
    {
        $endpoint = $this->issuer . '/v1/token';
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'code_verifier' => $codeVerifier,
        ];

        $ch = curl_init($endpoint);
        if ($ch === false) {
            throw new Exception('Unable to initialize token request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new Exception('Token request failed: ' . $message);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new Exception('Unexpected token response.');
        }

        if ($status >= 400) {
            $error = $data['error_description'] ?? $data['error'] ?? 'unknown_error';
            throw new Exception('Token request error: ' . $error);
        }

        return $data;
    }

    public function buildLogoutUrl(string $idTokenHint, string $postLogoutRedirectUri): string
    {
        $params = [
            'id_token_hint' => $idTokenHint,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ];

        return $this->issuer . '/v1/logout?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
