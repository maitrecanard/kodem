<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a case study of content/cases.json is shown. Archived cases stay
 * reachable by their URL but are left out of every list and not indexed.
 */
enum CaseVisibility: string
{
    case Home = 'accueil';
    case Listed = 'realisations';
    case Archived = 'archive';
}
