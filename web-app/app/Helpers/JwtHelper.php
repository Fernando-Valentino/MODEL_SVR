<?php

namespace App\Helpers;

class JwtHelper
{
    /**
     * Generate JWT Token signed with HS256
     *
     * @param array $payload
     * @param string $secret
     * @param int $expiryHours
     * @return string
     */
    public static function generate(array $payload, string $secret, int $expiryHours = 6): string
    {
        $header = (string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        
        $payload['exp'] = time() + ($expiryHours * 3600);
        $payload['iat'] = time();
        $payload = (string) json_encode($payload);
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);
        
        $signature = (string) hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Helper to base64url encode a string
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
