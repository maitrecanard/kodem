<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Services\VitrineContent;
use Illuminate\Support\Facades\File;

/**
 * Points base_path() at a scratch directory so VitrineContent reads a controlled
 * content/positioning.json. The using test calls restorePositioning() from tearDown().
 */
trait ServesTemporaryPositioning
{
    private ?string $originalBasePath = null;

    private ?string $temporaryBasePath = null;

    /**
     * @param  string|null  $json  raw file content, or null to leave the file absent
     */
    private function servePositioning(?string $json): void
    {
        $this->temporaryBasePath = sys_get_temp_dir().'/kodem-positioning-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->temporaryBasePath.'/content');

        if ($json !== null) {
            File::put($this->temporaryBasePath.'/content/positioning.json', $json);
        }

        $this->originalBasePath = $this->app->basePath();
        $this->app->setBasePath($this->temporaryBasePath);
        VitrineContent::flushCache();
    }

    private function restorePositioning(): void
    {
        if ($this->originalBasePath !== null) {
            $this->app->setBasePath($this->originalBasePath);
        }

        if ($this->temporaryBasePath !== null) {
            File::deleteDirectory($this->temporaryBasePath);
        }

        VitrineContent::flushCache();
    }
}
