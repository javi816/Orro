<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FirebaseTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function create(): View
    {
        $firebase = config('firebase.web');

        return view('auth.login', [
            'firebaseConfig' => array_filter($firebase),
            'firebaseConfigured' => filled($firebase['apiKey'] ?? null)
                && filled($firebase['authDomain'] ?? null)
                && filled($firebase['projectId'] ?? null),
        ]);
    }

    public function store(Request $request, FirebaseTokenVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $claims = $verifier->verify($data['id_token']);
        } catch (Throwable $exception) {
            Log::warning('Firebase ID token rejected.', ['reason' => $exception->getMessage()]);

            return response()->json([
                'message' => 'No se pudo validar la sesión de Firebase. Inicia sesión nuevamente.',
            ], 401);
        }

        $email = isset($claims['email']) ? mb_strtolower((string) $claims['email']) : null;
        $verified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        if ($email === null || ! $verified) {
            return response()->json([
                'message' => 'La cuenta de Google debe tener un correo verificado.',
            ], 403);
        }

        $allowedEmails = config('firebase.allowed_emails', []);

        if ($allowedEmails !== [] && ! in_array($email, array_map('mb_strtolower', $allowedEmails), true)) {
            return response()->json([
                'message' => 'Esta cuenta de Google no está autorizada para acceder.',
            ], 403);
        }

        $user = User::query()->where('firebase_uid', (string) $claims['sub'])->first()
            ?? User::query()->where('email', $email)->first()
            ?? new User;

        DB::transaction(function () use ($user, $claims, $email) {
            $user->fill([
                'name' => (string) ($claims['name'] ?? $email),
                'email' => $email,
                'avatar_url' => isset($claims['picture']) ? (string) $claims['picture'] : null,
                'firebase_uid' => (string) $claims['sub'],
            ]);

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            $user->save();
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('dashboard')]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
