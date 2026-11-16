<x-layouts.guest title="Iniciar sesión">
    <div class="rounded-3xl border border-white/10 bg-white p-8 shadow-2xl shadow-black/40 sm:p-10">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 text-xl font-bold text-slate-950 shadow-lg shadow-emerald-500/20">
                O
            </span>
            <div>
                <p class="text-lg font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</p>
                <p class="text-[11px] uppercase tracking-widest text-slate-400">Finanzas personales</p>
            </div>
        </div>

        <h1 class="mt-8 text-2xl font-semibold tracking-tight text-slate-900">Tus finanzas, en orden.</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Controla ingresos, gastos, ahorro y préstamos personales. Accede con tu cuenta de Google para consultar tu dinero disponible real.
        </p>

        <div class="mt-8">
            @if ($firebaseConfigured)
                <button
                    id="google-login-button"
                    type="button"
                    data-endpoint="{{ route('auth.firebase') }}"
                    class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                >
                    <span data-spinner class="hidden h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-emerald-600"></span>
                    <svg data-label class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.56c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.65l-3.56-2.77c-.98.66-2.23 1.06-3.72 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0012 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.11a6.6 6.6 0 010-4.22V7.05H2.18a11 11 0 000 9.9l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15A11 11 0 002.18 7.05l3.66 2.84C6.71 7.3 9.14 5.38 12 5.38z"/>
                    </svg>
                    <span data-label>Continuar con Google</span>
                </button>

                <div id="login-error" class="mt-4 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <script type="application/json" id="firebase-config">@json($firebaseConfig)</script>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">Firebase aún no está configurado.</p>
                    <p class="mt-1 text-xs leading-5">
                        Completa <code class="rounded bg-amber-100 px-1">FIREBASE_API_KEY</code>,
                        <code class="rounded bg-amber-100 px-1">FIREBASE_AUTH_DOMAIN</code> y
                        <code class="rounded bg-amber-100 px-1">FIREBASE_PROJECT_ID</code> en el archivo <code class="rounded bg-amber-100 px-1">.env</code>.
                        Consulta <code class="rounded bg-amber-100 px-1">FIREBASE_SETUP.md</code>.
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-8 flex items-start gap-2 border-t border-slate-100 pt-5 text-xs text-slate-400">
            <x-ui.icon name="lock" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
            <p>Autenticación delegada a Firebase. Esta aplicación nunca almacena tu contraseña de Google.</p>
        </div>
    </div>
</x-layouts.guest>
