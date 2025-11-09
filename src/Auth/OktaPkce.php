<?php

namespace CustomizationPortal\Auth;

class OktaPkce
{
    public static function generateCodeVerifier(int $length = 128): string
    {
        $length = max(43, min(128, $length));
        return self::base64UrlEncode(random_bytes($length));
    }

    public static function generateCodeChallenge(string $codeVerifier): string
    {
        return self::base64UrlEncode(hash('sha256', $codeVerifier, true));
    }

    public static function generateState(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
