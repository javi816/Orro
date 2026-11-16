<?php

namespace App\NativeComponents;

use App\Services\LocalAuthService;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * Pantalla de desbloqueo local.
 *
 * La primera vez crea el PIN; después solo lo verifica. No hay red ni
 * servicios externos implicados.
 */
class LockScreen extends NativeComponent
{
    public string $pin = '';

    public string $pinConfirmation = '';

    public string $error = '';

    public bool $creating = false;

    public function mount(): void
    {
        $this->creating = ! app(LocalAuthService::class)->hasPin();
        $this->error = '';
    }

    public function submit(?string $submitted = null): void
    {
        $pin = trim($submitted ?? $this->pin);
        $auth = app(LocalAuthService::class);

        if (! $auth->isValidPin($pin)) {
            $this->error = 'El PIN debe tener entre 4 y 8 dígitos.';
            $this->pin = '';

            return;
        }

        if ($this->creating) {
            if ($pin !== trim($this->pinConfirmation)) {
                $this->error = 'Los dos PIN no coinciden.';
                $this->pin = '';
                $this->pinConfirmation = '';

                return;
            }

            $auth->registerPin($pin);
            LocalAuthService::unlock();
            $this->replace('/');

            return;
        }

        if (! $auth->verifyPin($pin)) {
            $this->error = 'PIN incorrecto. Inténtalo de nuevo.';
            $this->pin = '';

            return;
        }

        LocalAuthService::unlock();
        $this->replace('/');
    }

    public function render(): View
    {
        return view('native.lock');
    }
}
