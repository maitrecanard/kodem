<?php

namespace App\Http\Controllers;

use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function store(Request $request, TrackingService $tracking): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_\.]+$/i'],
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_\-\.]+$/i'],
            'metadata' => ['nullable', 'array'],
        ]);

        $metadata = collect($validated['metadata'] ?? [])
            ->take(20)
            ->map(function ($v) {
                if (is_scalar($v) || $v === null) {
                    return is_string($v) ? substr($v, 0, 200) : $v;
                }
                return null; // on refuse l'imbriqué complexe
            })
            ->filter()
            ->all();

        $tracking->record($validated['type'], $validated['name'], $metadata, $request);

        return response()->json(['ok' => true]);
    }
}
