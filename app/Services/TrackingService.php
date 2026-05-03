<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\Request;

class TrackingService
{
    /**
     * Enregistre un événement. Aucune IP n'est stockée en clair : elle est
     * hachée avec APP_KEY (RGPD). Silent-fail : une erreur d'analytics ne
     * doit jamais propager vers la requête utilisateur.
     *
     * @param array<string,mixed> $metadata
     */
    public function record(string $type, string $name, array $metadata = [], ?Request $request = null): ?Event
    {
        try {
            $request ??= request();

            return Event::create([
                'type' => substr($type, 0, 60),
                'name' => substr($name, 0, 80),
                'url' => $request ? substr($request->path(), 0, 255) : null,
                'referer' => $request ? substr((string) $request->headers->get('referer'), 0, 255) ?: null : null,
                'ip_hash' => $request ? $this->hashIp((string) $request->ip()) : null,
                'session_hash' => $request ? $this->hashSession($request) : null,
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) ?: null : null,
                'user_id' => $request ? optional($request->user())->id : null,
                'metadata' => $metadata ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function hashIp(string $ip): string
    {
        return hash('sha256', $ip.config('app.key'));
    }

    public function hashSession(Request $request): ?string
    {
        try {
            $id = $request->hasSession() ? $request->session()->getId() : null;
            return $id ? hash('sha256', $id.config('app.key')) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
