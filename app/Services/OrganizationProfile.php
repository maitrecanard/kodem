<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VitrineContentUnreadable;

/**
 * Organization facts published in the JSON-LD of every page. The root view also serves the admin,
 * login and payment return pages: a broken content file is reported but never turns them into a 500.
 */
final class OrganizationProfile
{
    private const FALLBACK_DESCRIPTION = 'KODEM conçoit des outils métier sur mesure qui suppriment les tâches manuelles.';

    /**
     * @param  list<string>  $knowsAbout
     */
    private function __construct(
        public readonly string $description,
        public readonly array $knowsAbout,
    ) {}

    public static function current(): self
    {
        try {
            $positioning = VitrineContent::positioning();
        } catch (VitrineContentUnreadable $exception) {
            report($exception);

            return new self(self::FALLBACK_DESCRIPTION, []);
        }

        return new self($positioning['metier'], array_column($positioning['capabilities'], 'titre'));
    }
}
