<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use UnexpectedValueException;

class FirebaseAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_when_firebase_is_configured(): void
    {
        config(['firebase.web' => [
            'apiKey' => 'test-api-key',
            'authDomain' => 'demo.firebaseapp.com',
            'projectId' => 'demo',
        ]]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertSee('demo.firebaseapp.com', false);
    }

    public function test_login_page_warns_when_firebase_is_not_configured(): void
    {
        config(['firebase.web' => [
            'apiKey' => null,
            'authDomain' => null,
            'projectId' => null,
        ]]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Firebase aún no está configurado.');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/presupuesto')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_firebase_token_creates_session_user(): void
    {
        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->once()
                ->with('valid-token')
                ->andReturn($this->claims());
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('redirect', route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'firebase_uid' => 'firebase-uid-1',
            'email' => 'ana@example.com',
            'name' => 'Ana Pérez',
            'avatar_url' => 'https://example.com/ana.png',
        ]);

        $this->assertNull(User::query()->firstOrFail()->password);
    }

    public function test_repeated_login_updates_the_same_user(): void
    {
        $user = User::factory()->create([
            'firebase_uid' => 'firebase-uid-1',
            'email' => 'ana@example.com',
            'name' => 'Nombre antiguo',
        ]);

        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->andReturn($this->claims());
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();

        $this->assertSame(1, User::query()->count());
        $this->assertSame('Ana Pérez', $user->refresh()->name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_email_is_rejected(): void
    {
        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->once()
                ->andReturn($this->claims(['email_verified' => false]));
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
            ->assertForbidden();

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_email_outside_the_allowlist_is_rejected(): void
    {
        config(['firebase.allowed_emails' => ['owner@example.com']]);

        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->andReturn($this->claims());
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])
            ->assertForbidden();

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_email_inside_the_allowlist_is_accepted_case_insensitively(): void
    {
        config(['firebase.allowed_emails' => ['owner@example.com']]);

        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')->once()->andReturn($this->claims([
                'email' => 'OWNER@example.com',
            ]));
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'valid-token'])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->mock(FirebaseTokenVerifier::class, function (MockInterface $mock): void {
            $mock->shouldReceive('verify')
                ->once()
                ->andThrow(new UnexpectedValueException('firma inválida'));
        });

        $this->postJson(route('auth.firebase'), ['id_token' => 'bad-token'])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);

        $this->assertGuest();
    }

    public function test_token_is_required(): void
    {
        $this->postJson(route('auth.firebase'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function claims(array $overrides = []): array
    {
        return array_merge([
            'sub' => 'firebase-uid-1',
            'email' => 'ana@example.com',
            'email_verified' => true,
            'name' => 'Ana Pérez',
            'picture' => 'https://example.com/ana.png',
            'aud' => 'demo',
            'iss' => 'https://securetoken.google.com/demo',
        ], $overrides);
    }
}
