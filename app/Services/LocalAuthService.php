<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación local para la aplicación Android (sin red).
 *
 * La versión web mantiene Firebase/Google. En el dispositivo se crea un
 * único usuario local protegido por un PIN; el hash vive en la misma base
 * SQLite privada de la app, por lo que no se requiere ningún servicio remoto.
 *
 * El desbloqueo se guarda en memoria del proceso PHP: al cerrar o reiniciar
 * la app se vuelve a pedir el PIN.
 */
class LocalAuthService
{
    public const LOCAL_EMAIL = 'local@orro.app';

    private static bool $unlocked = false;

    public function user(): ?User
    {
        return User::query()->where('email', self::LOCAL_EMAIL)->first();
    }

    public function hasPin(): bool
    {
        return filled($this->user()?->password);
    }

    public function isValidPin(string $pin): bool
    {
        return preg_match('/^\d{4,8}$/', $pin) === 1;
    }

    public function registerPin(string $pin): User
    {
        $user = $this->user() ?? new User([
            'name' => 'Usuario local',
            'email' => self::LOCAL_EMAIL,
            'firebase_uid' => 'local-device',
        ]);

        // El cast `hashed` del modelo aplica bcrypt automáticamente.
        $user->password = $pin;

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        $user->save();

        return $user->refresh();
    }

    public function verifyPin(string $pin): bool
    {
        $user = $this->user();

        if ($user === null || blank($user->password)) {
            return false;
        }

        return Hash::check($pin, (string) $user->password);
    }

    public static function isUnlocked(): bool
    {
        return self::$unlocked;
    }

    public static function unlock(): void
    {
        self::$unlocked = true;
    }

    public static function lock(): void
    {
        self::$unlocked = false;
    }

    /**
     * Restablece el estado en memoria. Solo para pruebas.
     */
    public static function reset(): void
    {
        self::$unlocked = false;
    }
}
