<?php

namespace App\Services;

use App\Enums\CaseVisibility;
use App\Exceptions\VitrineContentUnreadable;
use JsonException;

class VitrineContent
{
    private const POSITIONING_FILE = 'content/positioning.json';

    /** Keys every page reading the positioning relies on. */
    private const POSITIONING_REQUIRED_KEYS = [
        'metier',
        'hero_title',
        'hero_baseline',
        'capabilities',
        'secteurs',
        'faq',
        'cta_primary',
        'cta_final',
    ];

    /** @var array<string,mixed>|null */
    private static ?array $positioningCache = null;

    /** @var array<int,array<string,mixed>>|null */
    private static ?array $casesCache = null;

    /** @var array<int,array<string,mixed>>|null */
    private static ?array $testimonialsCache = null;

    /** @var array<int,array<string,mixed>>|null */
    private static ?array $notesCache = null;

    /**
     * Positioning copy — niche framing, single source of truth.
     * Source : content/positioning.json.
     *
     * @return array<string,mixed>
     *
     * @throws VitrineContentUnreadable
     */
    public static function positioning(): array
    {
        if (self::$positioningCache === null) {
            $path = base_path(self::POSITIONING_FILE);
            $positioning = self::readJson($path);
            self::ensureRequiredKeys($positioning, self::POSITIONING_REQUIRED_KEYS, $path);
            self::$positioningCache = $positioning;
        }

        return self::$positioningCache;
    }

    /**
     * Forgets every loaded content file, so the next read goes back to disk.
     */
    public static function flushCache(): void
    {
        self::$positioningCache = null;
        self::$casesCache = null;
        self::$testimonialsCache = null;
        self::$notesCache = null;
    }

    /**
     * @return array<string,mixed>
     */
    private static function readJson(string $path): array
    {
        $raw = is_readable($path) ? file_get_contents($path) : false;

        if ($raw === false) {
            throw VitrineContentUnreadable::missing($path);
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw VitrineContentUnreadable::invalidJson($path, $exception);
        }

        if (! is_array($decoded)) {
            throw VitrineContentUnreadable::notAnObject($path);
        }

        return $decoded;
    }

    /**
     * @param  array<string,mixed>  $content
     * @param  list<string>  $requiredKeys
     *
     * @throws VitrineContentUnreadable
     */
    private static function ensureRequiredKeys(array $content, array $requiredKeys, string $path): void
    {
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $content)) {
                throw VitrineContentUnreadable::missingKey($path, $key);
            }
        }
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
     * Case studies featured on the home page, in file order.
     *
     * @return list<array<string,mixed>>
     */
    public static function homeCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (array $cas): bool => self::visibilityOf($cas) === CaseVisibility::Home
        ));
    }

    /**
     * Case studies listed on /realisations and in the sitemap, in file order.
     *
     * @return list<array<string,mixed>>
     */
    public static function listedCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (array $cas): bool => ! self::isArchived($cas)
        ));
    }

    /**
     * @param  array<string,mixed>  $cas
     */
    public static function isArchived(array $cas): bool
    {
        return self::visibilityOf($cas) === CaseVisibility::Archived;
    }

    /**
     * @param  array{visibilite?: string}  $cas
     */
    private static function visibilityOf(array $cas): ?CaseVisibility
    {
        return CaseVisibility::tryFrom($cas['visibilite'] ?? '');
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

    /**
     * Technical notes (KODEM "blog" — notes de fond, jamais des guides génériques).
     * Source : content/notes.json.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function notes(): array
    {
        if (self::$notesCache === null) {
            $raw = @file_get_contents(base_path('content/notes.json'));
            self::$notesCache = $raw ? (json_decode($raw, true) ?? []) : [];
        }

        return self::$notesCache;
    }

    /**
     * Single note by slug, or null if not found.
     *
     * @return array<string,mixed>|null
     */
    public static function note(string $slug): ?array
    {
        return collect(self::notes())->firstWhere('slug', $slug);
    }
}
