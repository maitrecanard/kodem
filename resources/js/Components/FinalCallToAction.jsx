import { trackClick } from '@/lib/track';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';

/**
 * Bloc d'appel final des pages vitrine. Porte lui même `kodem-reveal` :
 * ne jamais l'englober dans un autre élément révélé.
 *
 * @param {{
 *   ctaFinal: {titre: string, texte: string},
 *   trackingEvent: string,
 *   number?: string,
 *   className?: string,
 *   secondaryAction?: import('react').ReactNode,
 *   children?: import('react').ReactNode,
 * }} props
 */
export default function FinalCallToAction({ ctaFinal, trackingEvent, number, className = '', secondaryAction, children }) {
    return (
        <section className={`kodem-reveal ${className}`}>
            <div className="max-w-6xl mx-auto px-6 text-center">
                <SectionLabel number={number} className="justify-center">PROJET</SectionLabel>
                <h2 className="mt-3 text-kodem-h1 font-bold">{ctaFinal.titre}</h2>
                <p className="mt-4 text-acier max-w-xl mx-auto">{ctaFinal.texte}</p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <CodeButton href="/contact" onClick={() => trackClick(trackingEvent)}>
                        contacter_kodem()
                    </CodeButton>
                    {secondaryAction}
                </div>
                {children}
            </div>
        </section>
    );
}
