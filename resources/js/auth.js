import { initializeApp } from 'firebase/app';
import {
    GoogleAuthProvider,
    getAuth,
    signInWithPopup,
} from 'firebase/auth';

const configElement = document.getElementById('firebase-config');
const loginButton = document.getElementById('google-login-button');

if (configElement && loginButton) {
    const errorBox = document.getElementById('login-error');
    const buttonLabel = loginButton.querySelector('[data-label]');
    const spinner = loginButton.querySelector('[data-spinner]');

    const showError = (message) => {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const setLoading = (loading) => {
        loginButton.disabled = loading;
        loginButton.classList.toggle('opacity-70', loading);
        loginButton.classList.toggle('cursor-wait', loading);
        spinner?.classList.toggle('hidden', !loading);
        buttonLabel?.classList.toggle('hidden', loading);
    };

    try {
        const app = initializeApp(JSON.parse(configElement.textContent));
        const auth = getAuth(app);
        const provider = new GoogleAuthProvider();
        provider.setCustomParameters({ prompt: 'select_account' });

        loginButton.addEventListener('click', async () => {
            errorBox?.classList.add('hidden');
            setLoading(true);

            try {
                const result = await signInWithPopup(auth, provider);
                const idToken = await result.user.getIdToken();
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                const response = await fetch(loginButton.dataset.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ id_token: idToken }),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message ?? 'No se pudo iniciar sesión.');
                }

                window.location.assign(payload.redirect ?? '/');
            } catch (error) {
                if (error?.code === 'auth/popup-closed-by-user' || error?.code === 'auth/cancelled-popup-request') {
                    return;
                }

                showError(error?.message ?? 'No se pudo iniciar sesión con Google.');
            } finally {
                setLoading(false);
            }
        });
    } catch (error) {
        showError('La configuración pública de Firebase no es válida.');
        loginButton.disabled = true;
    }
}
