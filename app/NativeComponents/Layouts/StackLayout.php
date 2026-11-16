<?php

namespace App\NativeComponents\Layouts;

use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

/**
 * Layout de pila para pantallas empujadas (detalle, formularios).
 *
 * Activa el chrome nativo (NavHost/NavigationStack), que mantiene la pila
 * de navegación y permite que el botón atrás del sistema regrese a la
 * pantalla anterior en lugar de cerrar la app.
 */
class StackLayout extends NativeLayout
{
    public function navBar(NativeComponent $screen): ?NavBar
    {
        return NavBar::make()
            ->title($screen->navTitle())
            ->back();
    }
}
