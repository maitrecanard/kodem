{{-- La déclaration XML est émise par PHP et non écrite en dur : avec short_open_tag actif
     (cas du PHP embarqué de FrankenPHP en production), un « <?xml » littéral est lu comme
     une balise PHP ouvrante et la vue plante en erreur de syntaxe. --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <lastmod>{{ $lastmod }}</lastmod>
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
    </url>
@endforeach
</urlset>
