<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FastApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        // Mendapatkan URL FastAPI dari file .env (nama container docker: http://python_api:8000)
        $this->baseUrl = rtrim(env('PYTHON_ML_API_URL', config('services.fastapi.url', env('FASTAPI_URL', 'http://python_api:8000'))), '/');
    }

    /**
     * Mendapatkan headers untuk request, termasuk JWT Bearer token dinamis
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $secret = env('JWT_SECRET', 'rahasia-jwt-secret-key-123');

        if (auth()->check()) {
            $user = auth()->user();
            $token = \App\Helpers\JwtHelper::generate([
                'sub' => $user->username,
                'role' => $user->getRoleNames()->first() ?? 'operator',
            ], $secret, 6); // 6 Jam masa aktif
            $headers['Authorization'] = 'Bearer ' . $token;
        } else {
            // Fallback untuk CLI, pengujian, atau background job
            $token = \App\Helpers\JwtHelper::generate([
                'sub' => 'system_job',
                'role' => 'operator',
            ], $secret, 6);
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Mengirim GET request ke FastAPI
     *
     * @param string $endpoint
     * @param array $query
     * @return array|null
     */
    public function get(string $endpoint, array $query = [])
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout(120)
                            ->get("{$this->baseUrl}/{$endpoint}", $query);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("FastAPI GET Error on {$endpoint}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("FastAPI Connection Exception on {$endpoint}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengirim POST request ke FastAPI
     *
     * @param string $endpoint
     * @param array $data
     * @return array|null
     */
    public function post(string $endpoint, array $data = [])
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                            ->timeout(300)
                            ->post("{$this->baseUrl}/{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("FastAPI POST Error on {$endpoint}: " . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("FastAPI Connection Exception on {$endpoint}: " . $e->getMessage());
            return null;
        }
    }
}
