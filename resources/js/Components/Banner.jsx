import DiagonalPattern from '@/Components/DiagonalPattern';

/**
 * Bannière web — charte KODEM p.10 « Application Web — la bannière ».
 * Zone texturée pleine largeur s'insérant sous la barre de navigation :
 * hauteur ~400 px (esprit 18:5), motif diagonal en réserve d'image, voile
 * indigo dégradé 55 % → 15 % garantissant la lisibilité du titre. Titre
 * Display blanc + CTA primaire ancrés en bas à gauche (zone de sécurité ~80 px),
 * contenu aligné sur le conteneur central.
 */
export default function Banner({ title, cta, className = '' }) {
    return (
        <section
            className={`relative flex overflow-hidden bg-encre text-white min-h-[300px] md:min-h-[400px] ${className}`}
        >
            <DiagonalPattern className="absolute inset-0" aria-hidden="true" />
            <div
                className="absolute inset-0 bg-gradient-to-b from-encre/55 to-encre/15"
                aria-hidden="true"
            />
            <div className="relative z-10 mt-auto w-full">
                <div className="max-w-6xl mx-auto px-6 pb-8 md:pb-12 lg:pb-16 pt-8">
                    <div className="max-w-3xl">
                        <h1 className="text-4xl md:text-display font-bold leading-[1.05]">{title}</h1>
                        {cta && <div className="mt-6 flex flex-wrap gap-3">{cta}</div>}
                    </div>
                </div>
            </div>
        </section>
    );
}
