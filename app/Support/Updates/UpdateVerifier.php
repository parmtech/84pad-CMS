<?php

namespace App\Support\Updates;

final class UpdateVerifier
{
    public const MAX_BYTES = 268435456;

    /**
     * Verify a downloaded artifact against one of the site-owner's pinned
     * Ed25519 release public keys. Verification NEVER installs the artifact.
     */
    public function verify(string $path, string $signatureBase64): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')
            || ! is_file($path) || is_link($path)
            || ! is_readable($path) || filesize($path) === false
            || filesize($path) < 1 || filesize($path) > self::MAX_BYTES) {
            return false;
        }

        $signature = base64_decode($signatureBase64, true);
        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        $hash = hash_file('sha256', $path, true);
        if ($hash === false) {
            return false;
        }
        $message = "84PAD_CORE_UPDATE_V1\n".$hash;

        $keys = (array) config('84pad.release_public_keys', []);
        foreach ($keys as $keyBase64) {
            if (! is_string($keyBase64)) {
                continue;
            }
            $key = base64_decode($keyBase64, true);
            if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                continue;
            }
            if (sodium_crypto_sign_verify_detached($signature, $message, $key)) {
                return true;
            }
        }
        return false;
    }
}
