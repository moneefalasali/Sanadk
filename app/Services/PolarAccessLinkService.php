<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PolarAccessLinkService
{
    public function authorizeUrl(string $state = null): string
    {
        $config = config('services.polar');

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'scope' => 'user:hr:read user:profile:read user:info:read',
            'state' => $state,
        ]);

        return 'https://flow.polar.com/oauth2/authorization?' . $query;
    }

    public function exchangeCode(string $code): array
    {
        $config = config('services.polar');

        $response = Http::asForm()->post('https://polar-accesslink2.polar.com/oauth2/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirect'],
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Polar token exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $config = config('services.polar');

        $response = Http::asForm()->post('https://polar-accesslink2.polar.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Polar refresh failed: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchLatestHeartRate(string $userId, string $accessToken, string $start, string $end): array
    {
        $response = Http::withToken($accessToken)
            ->get("https://www.polaraccesslink.com/v3/users/{$userId}/hr", [
                'start' => $start,
                'end' => $end,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Polar HR fetch failed: ' . $response->body());
        }

        return $response->json();
    }
}
