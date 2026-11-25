<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyFirebaseAppCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('services.firebase_app_check.enabled', false)) {
            return $next($request);
        }

        $token = $request->header('X-Firebase-AppCheck');
        if (!$token) {
            return response()->json([
                'message' => 'App Check token diperlukan.',
            ], 401);
        }

        try {
            $jwks = $this->getJwks();
            $keys = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);

            $projectNumber = (string) config('services.firebase_app_check.project_number');
            $projectId = (string) config('services.firebase_app_check.project_id');

            $validAudiences = array_filter([
                $projectNumber !== '' ? "projects/{$projectNumber}" : null,
                $projectId !== '' ? "projects/{$projectId}" : null,
            ]);

            $aud = $decoded->aud ?? null;
            $audList = is_array($aud) ? $aud : [$aud];
            $audOk = !empty($validAudiences) && !empty(array_intersect($validAudiences, $audList));

            $iss = (string) ($decoded->iss ?? '');
            $issOk = $projectNumber !== '' && $iss === "https://firebaseappcheck.googleapis.com/{$projectNumber}";

            if (!$audOk || !$issOk) {
                Log::warning('Firebase App Check token invalid claims', [
                    'aud' => $aud,
                    'iss' => $iss,
                ]);
                return response()->json([
                    'message' => 'App Check token tidak valid.',
                ], 401);
            }

            $request->attributes->set('firebase_app_check', $decoded);

            return $next($request);
        } catch (\Throwable $e) {
            Log::warning('Firebase App Check verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Verifikasi App Check gagal.',
            ], 401);
        }
    }

    private function getJwks(): array
    {
        $cacheKey = 'firebase_app_check_jwks';
        $ttl = (int) config('services.firebase_app_check.jwks_cache_seconds', 21600);

        return Cache::remember($cacheKey, $ttl, function () {
            $url = (string) config('services.firebase_app_check.jwks_url', 'https://firebaseappcheck.googleapis.com/v1/jwks');
            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                throw new \RuntimeException('Gagal mengambil JWKS Firebase App Check.');
            }

            $jwks = $response->json();
            if (!is_array($jwks) || !isset($jwks['keys'])) {
                throw new \RuntimeException('Format JWKS tidak valid.');
            }

            return $jwks;
        });
    }
}
