<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * A content/*.json file of the public site is missing, unreadable, not valid JSON or incomplete.
 */
final class VitrineContentUnreadable extends RuntimeException
{
    public static function missing(string $path): self
    {
        return new self("Vitrine content file is not readable: {$path}");
    }

    public static function invalidJson(string $path, Throwable $previous): self
    {
        return new self("Vitrine content file is not valid JSON: {$path}", 0, $previous);
    }

    public static function notAnObject(string $path): self
    {
        return new self("Vitrine content file does not hold a JSON object or array: {$path}");
    }

    public static function missingKey(string $path, string $key): self
    {
        return new self("Vitrine content file lacks the required key '{$key}': {$path}");
    }
}
