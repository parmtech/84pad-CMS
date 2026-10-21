<?php

namespace Tests\Feature;

use App\Support\Updates\UpdateVerifier;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class UpdateVerifierTest extends TestCase
{
    public function test_pinned_ed25519_signature_accepts_unchanged_artifact_only(): void
    {
        if (! function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('Sodium extension unavailable');
        }

        $pair = sodium_crypto_sign_keypair();
        $public = sodium_crypto_sign_publickey($pair);
        $secret = sodium_crypto_sign_secretkey($pair);
        Config::set('84pad.release_public_keys', [base64_encode($public)]);

        $path = tempnam(sys_get_temp_dir(), '84pad-update-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, '84PAD deterministic test archive bytes');
            $hash = hash_file('sha256', $path, true);
            $signature = base64_encode(sodium_crypto_sign_detached(
                "84PAD_CORE_UPDATE_V1\n".$hash, $secret
            ));

            $verifier = new UpdateVerifier();
            $this->assertTrue($verifier->verify($path, $signature));

            file_put_contents($path, 'tampered archive');
            $this->assertFalse($verifier->verify($path, $signature));

            Config::set('84pad.release_public_keys', []);
            $this->assertFalse($verifier->verify($path, $signature));

            Config::set('84pad.release_public_keys', [base64_encode($public)]);
            $this->assertFalse($verifier->verify($path, 'not-valid-base64'));
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_archive_and_missing_trusted_keys_are_rejected(): void
    {
        Config::set('84pad.release_public_keys', []);
        $this->assertFalse((new UpdateVerifier())->verify(
            sys_get_temp_dir().'/no-such-84pad-update.zip', 'bad'
        ));
    }
}
