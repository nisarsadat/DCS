<?php

namespace App\Support;

class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $slice = intdiv(time(), 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::at($secret, $slice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function provisioningUri(string $secret, string $email, string $issuer = 'Dental Clinic'): string
    {
        $label = rawurlencode($issuer.':'.$email);

        return 'otpauth://totp/'.$label
            .'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer)
            .'&digits=6&period=30';
    }

    public static function at(string $secret, int $slice): string
    {
        $key = self::base32Decode($secret);
        $binary = pack('N*', 0).pack('N*', $slice);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $encoded .= self::ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';
        $length = strlen($secret);
        for ($i = 0; $i < $length; $i++) {
            $value = strpos(self::ALPHABET, $secret[$i]);
            if ($value === false) {
                continue;
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
