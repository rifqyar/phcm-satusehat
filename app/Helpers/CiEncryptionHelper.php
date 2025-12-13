<?php

namespace App\Helpers;

class CiEncryptionHelper
{
    /**
     * Decrypt data dari CodeIgniter 3 Encryption Library
     *
     * @param string $encrypted
     * @return string|null
     */
    public static function decrypt(string $encrypted): ?string
    {
        if (empty($encrypted)) {
            return null;
        }

        $key = env('CI_ENCRYPTION_KEY');

        if (!$key) {
            throw new \Exception('CI_ENCRYPTION_KEY tidak diset di .env');
        }

        // CI3 encryption payload = base64_encode(HMAC + IV + CIPHER_TEXT)
        $decoded = base64_decode($encrypted);

        if ($decoded === false) {
            return null;
        }

        $hmacLength = 64; // sha512 = 64 bytes
        $hmac = substr($decoded, 0, $hmacLength);
        $ivAndCipherText = substr($decoded, $hmacLength);

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($ivAndCipherText, 0, $ivLength);
        $cipherText = substr($ivAndCipherText, $ivLength);

        // Rebuild key seperti CI
        $key = hash('sha256', $key, true);

        // Validasi HMAC
        $calculatedHmac = hash_hmac(
            'sha512',
            $iv . $cipherText,
            $key,
            true
        );

        if (!hash_equals($hmac, $calculatedHmac)) {
            return null; // data corrupt / key beda
        }

        $plainText = openssl_decrypt(
            $cipherText,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $plainText ?: null;
    }
}
