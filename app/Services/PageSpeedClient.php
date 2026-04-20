<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PageSpeedClient
{
    /**
     * Interroge l'API PageSpeed Insights de Google et renvoie une structure
     * normalisée des Core Web Vitals.
     *
     * @return array{
     *     status: string,
     *     performance_score: int|null,
     *     lcp: string|null,
     *     cls: string|null,
     *     inp: string|null,
     *     fcp: string|null,
     *     tbt: string|null,
     *     raw: array<string,mixed>|null,
     *     error: string|null
     * }
     */
    public function run(string $url, string $strategy = 'mobile'): array
    {
        $endpoint = config('audit.pagespeed_endpoint');
        $apiKey = config('audit.pagespeed_api_key');

        $params = [
            'url' => $url,
            'strategy' => $strategy,
            'category' => 'performance',
        ];
        if ($apiKey) {
            $params['key'] = $apiKey;
        }

        try {
            $response = Http::timeout(45)->connectTimeout(15)->get($endpoint, $params);
        } catch (\Throwable $e) {
            return $this->failure('Appel PageSpeed impossible : '.$e->getMessage());
        }

        if (! $response->successful()) {
            return $this->failure('PageSpeed a répondu '.$response->status().'.');
        }

        $data = $response->json();
        $lighthouse = $data['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];
        $categories = $lighthouse['categories'] ?? [];

        return [
            'status' => 'completed',
            'performance_score' => isset($categories['performance']['score'])
                ? (int) round($categories['performance']['score'] * 100)
                : null,
            'lcp' => $audits['largest-contentful-paint']['displayValue'] ?? null,
            'cls' => $audits['cumulative-layout-shift']['displayValue'] ?? null,
            'inp' => $audits['interaction-to-next-paint']['displayValue'] ?? null,
            'fcp' => $audits['first-contentful-paint']['displayValue'] ?? null,
            'tbt' => $audits['total-blocking-time']['displayValue'] ?? null,
            'strategy' => $strategy,
            'fetched_at' => now()->toIso8601String(),
            'raw' => null, // on ne stocke pas la payload complète (~1 Mo)
            'error' => null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function failure(string $msg): array
    {
        return [
            'status' => 'failed',
            'performance_score' => null,
            'lcp' => null, 'cls' => null, 'inp' => null, 'fcp' => null, 'tbt' => null,
            'strategy' => null,
            'fetched_at' => now()->toIso8601String(),
            'raw' => null,
            'error' => $msg,
        ];
    }
}
