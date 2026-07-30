import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import SectionLabel from '@/Components/SectionLabel';
import ContactBlockMono from '@/Components/ContactBlockMono';
import { trackClick } from '@/lib/track';

export default function NoteShow({ meta, note }) {
    const sections = note?.sections || [];

    return (
        <PublicLayout meta={meta}>
            <div className="max-w-3xl mx-auto px-6 pt-8">
                {/* Fil d'Ariane */}
                <nav aria-label="Fil d'Ariane" className="flex items-center gap-2 font-mono text-legende text-acier mb-8">
                    <Link href="/" className="hover:text-cobalt-600 transition">Accueil</Link>
                    <span aria-hidden="true">/</span>
                    <Link href="/notes" className="hover:text-cobalt-600 transition">Notes</Link>
                    <span aria-hidden="true">/</span>
                    <span className="text-encre" aria-current="page">{note?.titre}</span>
                </nav>

                {note?.date && (
                    <span className="animate-kodem-slide font-mono text-legende text-acier">{note.date}</span>
                )}

                <h1 className="mt-3 text-kodem-h1 md:text-display font-bold text-encre leading-tight mb-4">
                    {note?.titre}
                </h1>

                {note?.resume && (
                    <p className="animate-kodem-slide text-lg text-acier mb-12">{note.resume}</p>
                )}

                {/* Corps de la note */}
                {sections.map((s, i) => (
                    <section key={i} className="kodem-reveal mb-10">
                        {s.titre && (
                            <SectionLabel number={String(i + 1).padStart(2, '0')}>
                                {s.titre.toUpperCase()}
                            </SectionLabel>
                        )}
                        {s.corps && (
                            <p className="mt-4 text-encre leading-relaxed">{s.corps}</p>
                        )}
                    </section>
                ))}

                {/* Renvoi vers la réalisation liée */}
                {note?.cas_lie && (
                    <p className="mt-12 font-mono text-legende">
                        <Link
                            href={`/realisations/${note.cas_lie}`}
                            onClick={() => trackClick('note_to_case', { slug: note.cas_lie })}
                            className="text-cobalt-600 hover:text-cobalt-800 transition"
                        >
                            → voir la réalisation liée
                        </Link>
                    </p>
                )}

                {/* CTA */}
                <div className="kodem-reveal mt-16 pt-10 border-t border-brume grid md:grid-cols-2 gap-8 items-start">
                    <div>
                        <p className="text-lg font-medium text-encre mb-2">
                            Un dispositif similaire à concevoir ?
                        </p>
                        <p className="text-acier text-sm mb-6">
                            Décrivez votre besoin — matériel, lieu, contraintes — et nous vous répondons sous 48 h.
                        </p>
                        <Link
                            href="/contact"
                            onClick={() => trackClick('note_cta_contact', { slug: note?.slug })}
                            className="inline-flex items-center rounded-kodem bg-cobalt-600 px-5 py-3 text-white font-medium hover:bg-cobalt-700 transition"
                        >
                            Parler de votre projet
                        </Link>
                    </div>
                    <ContactBlockMono />
                </div>
            </div>
        </PublicLayout>
    );
}
