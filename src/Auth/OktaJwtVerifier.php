<?php

namespace CustomizationPortal\Auth;

use Exception;

class OktaJwtVerifier
{
    private string $issuer;
    private string $clientId;
    private array $jwksCache = [];

    public function __construct(string $issuer, string $clientId)
    {
        $this->issuer = rtrim($issuer, '/');
        $this->clientId = $clientId;
    }

    /**
     * @param array<string, mixed> $token
     * @param string|null $nonce
     * @return array<string, mixed>
     * @throws Exception
     */
    public function verify(string $jwt, ?string $nonce = null): array
    {
        [$header64, $payload64, $signature64] = explode('.', $jwt);

        $header = json_decode($this->base64UrlDecode($header64), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($this->base64UrlDecode($payload64), true, 512, JSON_THROW_ON_ERROR);
        $signature = $this->base64UrlDecode($signature64);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new Exception('Unsupported token algorithm.');
        }

        $kid = $header['kid'] ?? null;
        if (!$kid) {
            throw new Exception('Token key ID missing.');
        }

        $publicKey = $this->getKeyForKid($kid);

        $verified = openssl_verify("{$header64}.{$payload64}", $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new Exception('Unable to verify token signature.');
        }

        $now = time();
        if (($payload['iss'] ?? null) !== $this->issuer) {
            throw new Exception('Token issuer mismatch.');
        }

        $aud = $payload['aud'] ?? null;
        $audiences = is_array($aud) ? $aud : [$aud];
        if (!in_array($this->clientId, $audiences, true)) {
            throw new Exception('Token audience mismatch.');
        }

        if (($payload['exp'] ?? 0) < $now) {
            throw new Exception('Token expired.');
        }

        if (($payload['iat'] ?? 0) > $now + 300) {
            throw new Exception('Token issued in the future.');
        }

        if ($nonce !== null && ($payload['nonce'] ?? null) !== $nonce) {
            throw new Exception('Nonce mismatch.');
        }

        return $payload;
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }

    private function getKeyForKid(string $kid)
    {
        if (!isset($this->jwksCache[$kid])) {
            $jwks = $this->fetchJwks();
            foreach ($jwks['keys'] as $key) {
                if (($key['kid'] ?? null) === $kid) {
                    $this->jwksCache[$kid] = $this->createPublicKeyFromJwk($key);
                    break;
                }
            }
        }

        if (!isset($this->jwksCache[$kid])) {
            throw new Exception('Unable to locate key for token.');
        }

        return $this->jwksCache[$kid];
    }

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    private function fetchJwks(): array
    {
        $jwksUri = $this->issuer . '/v1/keys';
        $response = file_get_contents($jwksUri);
        if ($response === false) {
            throw new Exception('Unable to fetch Okta JWKS.');
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        return $data;
    }

    /**
     * @param array<string, mixed> $jwk
     * @return resource
     */
    private function createPublicKeyFromJwk(array $jwk)
    {
        $pem = $this->jwkToPem($jwk);
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            throw new Exception('Unable to parse public key.');
        }

        return $publicKey;
    }

    /**
     * @param array<string, mixed> $jwk
     */
    private function jwkToPem(array $jwk): string
    {
        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);

        $components = [
            'modulus' => [
                'int' => $n,
            ],
            'publicExponent' => [
                'int' => $e,
            ],
        ];

        $sequence = $this->encodeDerSequence([
            $this->encodeDerInteger($components['modulus']['int']),
            $this->encodeDerInteger($components['publicExponent']['int'])
        ]);

        $bitString = $this->encodeDerBitString($sequence);
        $algorithmIdentifier = $this->encodeDerSequence([
            $this->encodeDerObjectIdentifier('1.2.840.113549.1.1.1'),
            $this->encodeDerNull(),
        ]);

        $subjectPublicKeyInfo = $this->encodeDerSequence([
            $algorithmIdentifier,
            $bitString,
        ]);

        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n");
        $pem .= "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    private function encodeDerInteger(string $value): string
    {
        if ($value === '') {
            $value = "\x00";
        }

        if (ord($value[0]) > 0x7F) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->encodeDerLength(strlen($value)) . $value;
    }

    private function encodeDerSequence(array $elements): string
    {
        $sequence = implode('', $elements);
        return "\x30" . $this->encodeDerLength(strlen($sequence)) . $sequence;
    }

    private function encodeDerBitString(string $value): string
    {
        $value = "\x00" . $value;
        return "\x03" . $this->encodeDerLength(strlen($value)) . $value;
    }

    private function encodeDerNull(): string
    {
        return "\x05\x00";
    }

    private function encodeDerObjectIdentifier(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $first = (40 * $parts[0]) + $parts[1];
        $encoded = chr($first);
        for ($i = 2, $len = count($parts); $i < $len; $i++) {
            $encoded .= $this->encodeBase128($parts[$i]);
        }

        return "\x06" . $this->encodeDerLength(strlen($encoded)) . $encoded;
    }

    private function encodeBase128(int $value): string
    {
        $result = '';
        while ($value > 0) {
            $result = chr(($value & 0x7F) | 0x80) . $result;
            $value >>= 7;
        }

        if ($result === '') {
            $result = "\x00";
        } else {
            $result[strlen($result) - 1] = chr(ord($result[strlen($result) - 1]) & 0x7F);
        }

        return $result;
    }

    private function encodeDerLength(int $length): string
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}
