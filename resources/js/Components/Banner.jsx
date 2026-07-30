import DiagonalPattern from '@/Components/DiagonalPattern';

/**
 * Bannière web — charte KODEM p.10 « Application Web — la bannière ».
 * Zone pleine largeur s'insérant sous la barre de navigation :
 * hauteur ~400 px (esprit 18:5), voile indigo dégradé garantissant la
 * lisibilité du titre. Titre Display blanc + CTA primaire ancrés en bas à
 * gauche (zone de sécurité ~80 px), contenu aligné sur le conteneur central.
 *
 * Fond : `image` (WebP servi depuis /images/) si fournie, sinon le motif
 * diagonal qui tient lieu de réserve d'image. Sur photo, le voile reste très
 * doux pour ne pas écraser le visuel — c'est l'ombre portée du titre qui
 * assure le contraste, pas l'assombrissement du fond.
 * L'image est décorative par défaut (le <h1> porte le sens) ; passer
 * `imageAlt` pour la décrire quand elle apporte une information propre.
 *
 * `imageSources` : variantes du MÊME visuel à des définitions croissantes,
 * sous la forme [{ src, width }]. La bannière étant pleine largeur, le
 * navigateur arbitre via srcset + sizes="100vw" : il DÉSÉCHANTILLONNE la
 * grande variante sur écran large ou HiDPI (rendu net) au lieu de
 * suréchantillonner la petite (rendu pixélisé), et sert la légère sur mobile.
 *
 * @param {{ title: string, cta?: React.ReactNode, image?: string, imageSources?: Array<{src: string, width: number}>, imageAlt?: string, className?: string }} props
 */
const IMAGE_PATH = /^\/images\/[\w/-]+\.webp$/;

export default function Banner({
    title,
    cta,
    image,
    imageSources = [],
    imageAlt = '',
    className = '',
}) {
    const hasImage = typeof image === 'string' && IMAGE_PATH.test(image);
    const srcSet = imageSources
        .filter((s) => IMAGE_PATH.test(s?.src ?? '') && Number.isInteger(s?.width))
        .map((s) => `${s.src} ${s.width}w`)
        .join(', ');

    return (
        <section
            className={`relative flex overflow-hidden bg-encre text-white min-h-[300px] md:min-h-[400px] ${className}`}
        >
            {hasImage ? (
                <img
                    src={image}
                    srcSet={srcSet || undefined}
                    sizes={srcSet ? '100vw' : undefined}
                    alt={imageAlt}
                    aria-hidden={imageAlt === '' ? 'true' : undefined}
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    className="absolute inset-0 h-full w-full object-cover"
                />
            ) : (
                <DiagonalPattern className="absolute inset-0" aria-hidden="true" />
            )}
            {hasImage ? (
                <>
                    {/* Scrim vertical : sombre en BAS, où sont ancrés le titre et les CTA ;
                        transparent en haut pour laisser respirer la photo. */}
                    <div
                        className="absolute inset-0 bg-gradient-to-t from-encre/60 via-encre/15 to-transparent"
                        aria-hidden="true"
                    />
                    {/* Scrim horizontal : renforce le coin bas-gauche (zone de texte)
                        sans voiler la droite de la composition. */}
                    <div
                        className="absolute inset-0 bg-gradient-to-r from-encre/35 via-transparent to-transparent"
                        aria-hidden="true"
                    />
                </>
            ) : (
                <div
                    className="absolute inset-0 bg-gradient-to-b from-encre/55 to-encre/15"
                    aria-hidden="true"
                />
            )}
            <div className="relative z-10 mt-auto w-full">
                <div className="max-w-6xl mx-auto px-6 pb-8 md:pb-12 lg:pb-16 pt-8">
                    <div className="max-w-3xl">
                        {/* `text-white` explicite : la règle de base de app.css applique
                            `text-encre` à tous les h1-h6 et l'emporte sur la couleur héritée
                            de la section — sans ça le titre est indigo sur fond indigo. */}
                        <h1 className="text-4xl md:text-display font-bold leading-[1.05] text-white [text-shadow:0_2px_16px_rgba(11,27,77,0.95),0_1px_3px_rgba(11,27,77,0.8)]">
                            {title}
                        </h1>
                        {cta && <div className="animate-kodem-rise mt-6 flex flex-wrap gap-3">{cta}</div>}
                    </div>
                </div>
            </div>
        </section>
    );
}
