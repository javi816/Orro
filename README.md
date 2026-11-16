# Orro — Finanzas personales

Aplicación web monolítica en Laravel para llevar ingresos, gastos, ahorro y préstamos personales mes a mes. La interfaz está en español y el acceso se realiza exclusivamente con una cuenta de Google mediante Firebase Authentication.

## Funcionalidad

- **Dashboard** con dinero disponible, gastos pendientes, ahorro del mes, deuda y próxima reposición.
- **Ingresos** esperados generados desde fuentes configurables; se marcan como recibidos.
- **Gastos fijos** generados desde plantillas y **gastos variables** registrados en el mes.
- **Ahorro** con saldo inicial único, aportes, retiros y reposiciones.
- **Préstamos del ahorro** con vencimiento al mes siguiente y reposiciones parciales o totales.
- **Presupuesto variable** por período.
- **Cierre mensual** con validación en el servidor, resumen guardado y bloqueo del período.
- **Historial** de períodos con sus cifras de cierre.

## Requisitos

- PHP 8.4 con las extensiones `bcmath`, `openssl`, `pdo_sqlite` y `sqlite3`.
- Composer.
- Node.js 20+ y npm.
- SQLite.

## Puesta en marcha

```bash
composer install
copy .env.example .env          # Windows
# cp .env.example .env          # macOS / Linux

php artisan key:generate

# Crea el archivo si todavía no existe:
# Windows
type nul > database\database.sqlite
# macOS / Linux
# touch database/database.sqlite

php artisan migrate --seed

npm install
npm run build                   # o `npm run dev` durante el desarrollo

php artisan serve
```

La aplicación queda disponible en `http://localhost:8000`. Para entrar hace falta configurar Firebase; consulta **[FIREBASE_SETUP.md](FIREBASE_SETUP.md)**.

> No se necesitan credenciales de cuenta de servicio. La aplicación solo usa la configuración web pública de Firebase y verifica los ID tokens contra las claves públicas de Google.

## Variables de entorno relevantes

| Variable | Descripción |
| --- | --- |
| `APP_TIMEZONE` | Zona horaria usada para decidir el mes financiero actual. Por defecto `America/Bogota`. |
| `FIREBASE_PROJECT_ID` | ID del proyecto de Firebase. Se usa para validar `iss` y `aud`. |
| `FIREBASE_API_KEY` | API key pública del SDK web. |
| `FIREBASE_AUTH_DOMAIN` | Dominio de autenticación (`<proyecto>.firebaseapp.com`). |
| `FIREBASE_ALLOWED_EMAILS` | Opcional. Correos autorizados separados por comas. Si está vacío se acepta cualquier cuenta de Google verificada. |

Después de cambiar el `.env` ejecuta `php artisan config:clear` si tienes la configuración cacheada.

## Reglas de negocio

### Dinero disponible

```
ingresos recibidos + préstamos recibidos
− (gastos pagados + gastos fijos pendientes + presupuesto variable restante
   + ahorro faltante + reposiciones vencidas)
```

El presupuesto variable restante es `presupuesto variable − gasto variable del mes`. Los ingresos pendientes no suman al disponible y solo generan una advertencia al cerrar.

### Ahorro y préstamos

```
saldo de ahorro = saldo inicial + aportes + reposiciones − retiros
disponible para prestar = max(saldo de ahorro − mínimo protegido, 0)
```

Crear un préstamo registra automáticamente un retiro del ahorro por el mismo importe. Reponer un préstamo registra una reposición. No se puede prestar por encima del disponible ni reponer más de lo pendiente.

### Cierre de período

Un período se puede cerrar cuando:

1. Todos los gastos fijos están pagados.
2. Todos los gastos variables están pagados.
3. Se cumplió la meta mensual de ahorro.
4. No hay reposiciones vencidas pendientes.

Los ingresos pendientes solo producen una advertencia. Al cerrar se guardan `closed_at` y `closing_summary`; el período pasa a solo lectura y **no se puede reabrir**. Las validaciones viven en los servicios, no solo en la interfaz.

Los datos históricos no cambian al modificar plantillas o fuentes: los registros generados conservan el importe con el que se crearon.

## Arquitectura

```
Ruta → Controlador → Servicio → Repositorio → Modelo Eloquent → Blade
```

- `app/Repositories`: consultas y persistencia.
- `app/Services`: reglas de negocio y cálculos financieros.
- `app/Http/Controllers`: orquestación de peticiones y respuestas.
- `app/Models`: modelos Eloquent.
- `resources/views`: interfaz Blade.
- `app/Support/Money.php`: aritmética monetaria con BCMath (sin `float`).

No se usan DTOs, acciones, casos de uso, interfaces de repositorio, colas ni API REST.

## Modelo de datos

- `settings` — meta mensual de ahorro y mínimo protegido.
- `periods` — año, mes, presupuesto variable, estado, cierre y resumen.
- `income_sources`, `incomes` — fuentes y ingresos del período.
- `expense_templates`, `expenses` — gastos fijos recurrentes y gastos del período.
- `savings_movements` — saldo inicial, aportes, retiros y reposiciones.
- `savings_loans` — préstamos del ahorro y su período de vencimiento.
- `users` — usuarios locales identificados por `firebase_uid`.

Restricciones destacadas: `DECIMAL(12,2)` para importes, claves foráneas activas en SQLite, `unique(year, month)` en períodos y unicidad de fuente/plantilla por período.

## Pruebas

```bash
php artisan test --compact
```

La suite cubre generación idempotente de períodos, inmutabilidad histórica, fórmula de dinero disponible, ahorro y préstamos, cierre y protección de períodos cerrados, autenticación Firebase, verificación de tokens y renderizado de todas las páginas.

## Rutas principales

| Método | Ruta | Descripción |
| --- | --- | --- |
| GET | `/` | Dashboard |
| GET/PUT | `/presupuesto` | Presupuesto variable |
| GET | `/ingresos` | Ingresos del período |
| GET/POST | `/gastos` | Gastos del período |
| GET/POST | `/ahorros` | Movimientos de ahorro |
| GET/POST | `/prestamos` | Préstamos del ahorro |
| GET | `/periodos` | Historial |
| GET | `/periodos/{period}` | Detalle y cierre |
| GET/PUT | `/configuracion` | Ajustes, fuentes y gastos fijos |

## Seguridad

- No se almacenan contraseñas de Firebase ni de Google.
- Los ID tokens se verifican en el servidor: firma RS256 con certificados públicos de Google, expiración, emisor, audiencia y sujeto.
- Las rutas de datos están protegidas por el middleware `auth`.
- La lista opcional de correos permite restringir el acceso, recomendable porque los datos son compartidos y no están separados por usuario.
- No se versiona el archivo `.env` ni credenciales.
