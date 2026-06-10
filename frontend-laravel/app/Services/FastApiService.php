<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FastApiService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        // Mendapatkan URL FastAPI dari file .env (nama container docker: http://python_api:8000)
        $this->baseUrl = rtrim(config('services.fastapi.url', env('FASTAPI_URL', 'http://python_api:8000')), '/');
        $this->apiKey = config('services.fastapi.key', env('FASTAPI_KEY', 'default-fallback-key'));
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
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/{$endpoint}", $query);

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
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/{$endpoint}", $data);

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
