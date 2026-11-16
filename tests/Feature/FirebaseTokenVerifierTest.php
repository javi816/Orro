<?php

namespace Tests\Feature;

use App\Services\FirebaseTokenVerifier;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use UnexpectedValueException;

class FirebaseTokenVerifierTest extends TestCase
{
    private string $privateKey = '';

    private string $publicKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        [$this->privateKey, $this->publicKey] = $this->generateKeyPair();

        config(['firebase.project_id' => 'demo-project']);

        Http::fake([
            'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com' => Http::response([
                'test-kid' => $this->publicKey,
            ]),
        ]);
    }

    public function test_valid_token_is_accepted(): void
    {
        $claims = $this->verifier()->verify($this->token());

        $this->assertSame('uid-123', $claims['sub']);
        $this->assertSame('demo-project', $claims['aud']);
        $this->assertSame('https://securetoken.google.com/demo-project', $claims['iss']);
        $this->assertSame('ana@example.com', $claims['email']);
    }

    public function test_token_with_wrong_issuer_is_rejected(): void
    {
        $token = $this->token(['iss' => 'https://securetoken.google.com/otro-proyecto']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('emisor');

        $this->verifier()->verify($token);
    }

    public function test_token_with_wrong_audience_is_rejected(): void
    {
        $token = $this->token(['aud' => 'otro-proyecto']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('audiencia');

        $this->verifier()->verify($token);
    }

    public function test_expired_token_is_rejected(): void
    {
        $now = time();

        $token = $this->token([
            'iat' => $now - 7200,
            'exp' => $now - 3600,
        ]);

        $this->expectException(ExpiredException::class);

        $this->verifier()->verify($token);
    }

    public function test_token_signed_with_another_key_is_rejected(): void
    {
        [$otherPrivateKey] = $this->generateKeyPair();

        $token = $this->token([], $otherPrivateKey);

        $this->expectException(SignatureInvalidException::class);

        $this->verifier()->verify($token);
    }

    public function test_token_without_subject_is_rejected(): void
    {
        $token = $this->token(['sub' => '']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('usuario');

        $this->verifier()->verify($token);
    }

    public function test_token_issued_in_the_future_is_rejected(): void
    {
        $now = time();

        $token = $this->token([
            'iat' => $now + 3600,
            'exp' => $now + 7200,
        ]);

        $this->expectException(UnexpectedValueException::class);

        $this->verifier()->verify($token);
    }

    public function test_missing_project_id_is_rejected(): void
    {
        config(['firebase.project_id' => '']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('FIREBASE_PROJECT_ID');

        $this->verifier()->verify($this->token());
    }

    private function verifier(): FirebaseTokenVerifier
    {
        return $this->app->make(FirebaseTokenVerifier::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function token(array $overrides = [], ?string $privateKey = null): string
    {
        $now = time();

        $payload = array_merge([
            'iss' => 'https://securetoken.google.com/demo-project',
            'aud' => 'demo-project',
            'sub' => 'uid-123',
            'iat' => $now,
            'exp' => $now + 3600,
            'email' => 'ana@example.com',
            'email_verified' => true,
        ], $overrides);

        return JWT::encode($payload, $privateKey ?? $this->privateKey, 'RS256', 'test-kid');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function generateKeyPair(): array
    {
        $config = dirname(__DIR__).'/Fixtures/openssl.cnf';

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ]);

        $this->assertNotFalse($resource);

        openssl_pkey_export($resource, $privateKey, null, ['config' => $config]);
        $details = openssl_pkey_get_details($resource);

        $this->assertIsArray($details);

        return [$privateKey, $details['key']];
    }
}
