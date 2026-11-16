# Configuración de Firebase (Google)

Orro usa **Firebase Authentication con Google** y valida el ID token en el servidor. No se necesita cuenta de servicio, archivo JSON privado ni contraseñas de Google.

## 1. Crear el proyecto de Firebase

1. Entra en [console.firebase.google.com](https://console.firebase.google.com).
2. Crea un proyecto (o usa uno existente).
3. Anota el **Project ID**; lo verás en *Configuración del proyecto → General*.

## 2. Habilitar el acceso con Google

1. Ve a *Authentication → Sign-in method* (Métodos de acceso).
2. Selecciona **Google**.
3. Actívalo, elige un correo de asistencia y guarda.

Si el proyecto está en modo de prueba, agrega tu cuenta como usuario de prueba en la pantalla de consentimiento de Google, o publica la app.

## 3. Registrar la aplicación web

1. En *Configuración del proyecto → General → Tus apps*, pulsa el icono **`</>`** (Web).
2. Registra la app con un nombre, por ejemplo `Orro`.
3. Copia los valores de `firebaseConfig`:
   - `apiKey`
   - `authDomain`
   - `projectId`

No copies ni publiques credenciales de cuenta de servicio: esta aplicación no las usa.

## 4. Configurar el archivo `.env`

Copia `.env.example` a `.env` si aún no lo hiciste y completa:

```dotenv
FIREBASE_PROJECT_ID=tu-proyecto
FIREBASE_API_KEY=tu-api-key-publica
FIREBASE_AUTH_DOMAIN=tu-proyecto.firebaseapp.com
```

Recomendado para una aplicación personal con datos compartidos:

```dotenv
FIREBASE_ALLOWED_EMAILS=tu-correo@gmail.com,otro-correo@gmail.com
```

Sin esta lista, cualquier cuenta de Google con correo verificado puede entrar. La comparación no distingue mayúsculas.

Luego limpia la caché de configuración:

```bash
php artisan config:clear
```

## 5. Dominios autorizados

En *Authentication → Settings → Authorized domains*:

- `localhost` suele estar autorizado por defecto.
- Agrega el dominio donde publiques la aplicación.
- Si abres la app como `http://127.0.0.1:8000`, agrega también `127.0.0.1`.

El inicio de sesión con ventana emergente falla si el dominio actual no está autorizado.

## 6. Probar el acceso

1. Ejecuta `npm run build` (o `npm run dev`) y `php artisan serve`.
2. Abre `http://localhost:8000`.
3. Pulsa **Continuar con Google**.
4. El navegador obtiene un ID token y lo envía a `POST /auth/firebase`.
5. El servidor lo valida y crea una sesión Laravel.

Si la configuración web está incompleta, la pantalla de acceso muestra un aviso en lugar del botón.

## Cómo se valida el token

`App\Services\FirebaseTokenVerifier`:

1. Descarga los certificados públicos x509 de Google para `securetoken@system.gserviceaccount.com` y los cachea una hora.
2. Verifica la firma **RS256** usando el `kid` del encabezado.
3. Comprueba expiración y que el token no sea futuro.
4. Exige `iss = https://securetoken.google.com/<project_id>`.
5. Exige `aud = <project_id>`.
6. Exige un `sub` no vacío.

Después, `AuthController` comprueba que el correo esté verificado, aplica la lista opcional de correos y crea o actualiza el usuario local por `firebase_uid`. Después inicia la sesión Laravel.

Nunca se guardan contraseñas de Google/Firebase. La API key web es pública; la seguridad depende de la verificación del token y, opcionalmente, de la lista de correos autorizados.

## Problemas frecuentes

| Síntoma | Causa probable |
| --- | --- |
| «Firebase aún no está configurado» | Faltan `FIREBASE_API_KEY`, `FIREBASE_AUTH_DOMAIN` o `FIREBASE_PROJECT_ID`, o hay configuración cacheada. Ejecuta `php artisan config:clear`. |
| La ventana de Google no abre | Bloqueador de ventanas emergentes o dominio no autorizado. |
| `auth/unauthorized-domain` | Agrega el dominio actual en *Authentication → Settings → Authorized domains*. |
| `401` al enviar el token | Token vencido, firma inválida, proyecto equivocado o reloj del servidor desajustado. |
| `403` con correo verificado | La cuenta no está en `FIREBASE_ALLOWED_EMAILS`. |
| «La cuenta de Google debe tener un correo verificado» | El proveedor no devolvió `email_verified`. |
| Error de Vite / falta `manifest.json` | Ejecuta `npm run build` o `npm run dev`. |

## Seguridad

- No versiones `.env` ni archivos de credenciales.
- Mantén la lista de correos autorizados si los datos no deben ser públicos.
- Restringe los dominios autorizados en Firebase antes de publicar.
- No agregues credenciales de cuenta de servicio a este proyecto: no son necesarias para este flujo.
