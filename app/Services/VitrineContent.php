<?php

namespace App\Services;

class VitrineContent
{
    /** @var array<string,mixed>|null */
    private static ?array $positioningCache = null;

    /** @var array<int,array<string,mixed>>|null */
    private static ?array $casesCache = null;

    /** @var array<int,array<string,mixed>>|null */
    private static ?array $testimonialsCache = null;

    /**
     * Positioning copy — niche framing, single source of truth.
     * Source : content/positioning.json.
     *
     * @return array<string,mixed>
     */
    public static function positioning(): array
    {
        if (self::$positioningCache === null) {
            $raw = @file_get_contents(base_path('content/positioning.json'));
            self::$positioningCache = $raw ? (json_decode($raw, true) ?? []) : [];
        }

        return self::$positioningCache;
    }

    /**
     * All case studies.
     * Source : content/cases.json.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function cases(): array
    {
        if (self::$casesCache === null) {
            $raw = @file_get_contents(base_path('content/cases.json'));
            self::$casesCache = $raw ? (json_decode($raw, true) ?? []) : [];
        }

        return self::$casesCache;
    }

    /**
     * Single case study by slug, or null if not found.
     *
     * @return array<string,mixed>|null
     */
    public static function case(string $slug): ?array
    {
        return collect(self::cases())->firstWhere('slug', $slug);
    }

    /**
     * All testimonials.
     * Source : content/testimonials.json.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function testimonials(): array
    {
        if (self::$testimonialsCache === null) {
            $raw = @file_get_contents(base_path('content/testimonials.json'));
            self::$testimonialsCache = $raw ? (json_decode($raw, true) ?? []) : [];
        }

        return self::$testimonialsCache;
    }

    /**
     * Testimonials linked to a specific case study, filtered by cas_lie === $slug.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function testimonialsForCase(string $slug): array
    {
        return array_values(
            array_filter(self::testimonials(), fn ($t) => ($t['cas_lie'] ?? null) === $slug)
        );
    }
}
