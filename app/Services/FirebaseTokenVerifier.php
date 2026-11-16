<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

/**
 * Verifica ID tokens de Firebase contra las claves públicas de Google.
 *
 * Comprueba firma RS256, expiración, emisor, audiencia y sujeto. No usa
 * credenciales de cuenta de servicio ni almacena contraseñas.
 */
class FirebaseTokenVerifier
{
    private const CERTIFICATES_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /**
     * @return array<string, mixed>
     */
    public function verify(string $idToken): array
    {
        $projectId = trim((string) config('firebase.project_id'));

        if ($projectId === '') {
            throw new UnexpectedValueException('Firebase no está configurado: falta FIREBASE_PROJECT_ID.');
        }

        $cacheKey = 'firebase:public-keys:'.$projectId;
        $certificates = Cache::get($cacheKey);

        if (! is_array($certificates) || $certificates === []) {
            $certificates = $this->fetchCertificates($cacheKey);
        }

        try {
            $payload = $this->decode($idToken, $certificates);
        } catch (UnexpectedValueException) {
            // Una rotación de claves puede invalidar el kid conocido: refrescamos una vez.
            $payload = $this->decode($idToken, $this->fetchCertificates($cacheKey));
        }

        $issuer = 'https://securetoken.google.com/'.$projectId;

        if (($payload['iss'] ?? null) !== $issuer) {
            throw new UnexpectedValueException('El emisor del token no corresponde al proyecto de Firebase.');
        }

        if (($payload['aud'] ?? null) !== $projectId) {
            throw new UnexpectedValueException('La audiencia del token no corresponde al proyecto de Firebase.');
        }

        if (! isset($payload['sub']) || ! is_string($payload['sub']) || $payload['sub'] === '') {
            throw new UnexpectedValueException('El token no contiene un usuario válido.');
        }

        if (isset($payload['iat']) && (int) $payload['iat'] > time() + 60) {
            throw new UnexpectedValueException('El token fue emitido en el futuro.');
        }

        return $payload;
    }

    /**
     * @param  array<string, string>  $certificates
     * @return array<string, mixed>
     */
    private function decode(string $idToken, array $certificates): array
    {
        $keys = [];

        foreach ($certificates as $kid => $certificate) {
            $keys[(string) $kid] = new Key($certificate, 'RS256');
        }

        return (array) JWT::decode($idToken, $keys);
    }

    /**
     * @return array<string, string>
     */
    private function fetchCertificates(string $cacheKey): array
    {
        $response = Http::timeout(10)->acceptJson()->get(self::CERTIFICATES_URL);
        $response->throw();

        $certificates = $response->json();

        if (! is_array($certificates) || $certificates === []) {
            throw new UnexpectedValueException('No se pudieron obtener las claves públicas de Firebase.');
        }

        Cache::put($cacheKey, $certificates, now()->addHour());

        return $certificates;
    }
}
