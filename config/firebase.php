<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project
    |--------------------------------------------------------------------------
    |
    | Identificador del proyecto de Firebase. Se usa para validar el emisor
    | (iss) y la audiencia (aud) de los ID tokens.
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Cuentas autorizadas (opcional)
    |--------------------------------------------------------------------------
    |
    | Lista separada por comas de correos permitidos. Si está vacía se acepta
    | cualquier cuenta de Google verificada por Firebase. Al ser una aplicación
    | personal con datos compartidos, se recomienda limitar el acceso.
    |
    */

    'allowed_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FIREBASE_ALLOWED_EMAILS', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Configuración web pública
    |--------------------------------------------------------------------------
    |
    | Estos valores se envían al navegador para inicializar el SDK de Firebase.
    | No son secretos: la seguridad la aporta la verificación del ID token en
    | el servidor. No coloques aquí credenciales de cuenta de servicio.
    |
    */

    'web' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
    ],

];
