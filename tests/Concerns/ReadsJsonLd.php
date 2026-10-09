<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

/**
 * Lecture du bloc JSON-LD rendu côté serveur par app.blade.php : une requête
 * sans en-tête X-Inertia renvoie la page HTML complète qui le contient.
 */
trait ReadsJsonLd
{
    /**
     * @return list<array<string,mixed>>
     */
    private function jsonLdGraph(TestResponse $response): array
    {
        $matched = preg_match(
            '/<script\s+type="application\/ld\+json">(.*?)<\/script>/s',
            (string) $response->getContent(),
            $matches
        );

        $this->assertSame(1, $matched, 'la page doit contenir un bloc <script type="application/ld+json">');

        $data = json_decode($matches[1], true);

        $this->assertIsArray($data, 'le contenu ld+json doit être du JSON valide');
        $this->assertArrayHasKey('@graph', $data, 'le JSON-LD doit utiliser un @graph');
        $this->assertIsList($data['@graph'], 'le @graph du JSON-LD doit être une liste de nœuds');

        return $data['@graph'];
    }
}
