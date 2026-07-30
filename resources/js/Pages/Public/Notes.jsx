import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';
import CodeButton from '@/Components/CodeButton';
import { trackClick } from '@/lib/track';

export default function Notes({ meta, notes = [] }) {
    return (
        <PublicLayout meta={meta}>
            <Banner
                image="/images/banniere-kodem.webp"
                imageSources={[
                    { src: '/images/banniere-kodem.webp', width: 1006 },
                    { src: '/images/banniere-kodem-2x.webp', width: 2012 },
                ]}
                title="Notes techniques"
            />

            <section className="max-w-6xl mx-auto px-6 pt-16 pb-8">
                <SectionLabel>NOTES</SectionLabel>
                <p className="animate-kodem-fade mt-4 max-w-2xl text-lg text-acier">
                    Des notes de fond sur les dispositifs connectés sur site : choix techniques,
                    retours d'expérience, compromis documentés. Aucun guide générique — uniquement
                    des sujets où KODEM a une position à défendre.
                </p>
            </section>

            {notes.length > 0 ? (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 pb-24">
                    <div className="grid gap-6 md:grid-cols-2">
                        {notes.map((note) => (
                            <article
                                key={note.slug}
                                className="kodem-card bg-white rounded-kodem border border-brume p-6 shadow-sm hover:shadow-md flex flex-col gap-4"
                            >
                                {note.date && (
                                    <span className="font-mono text-legende text-acier">{note.date}</span>
                                )}
                                <h2 className="text-kodem-h2 font-bold text-encre">{note.titre}</h2>
                                <p className="text-sm text-acier flex-1">{note.resume}</p>
                                <div>
                                    <CodeButton
                                        href={`/notes/${note.slug}`}
                                        onClick={() => trackClick('note_card_cta', { slug: note.slug })}
                                    >
                                        lire_la_note()
                                    </CodeButton>
                                </div>
                            </article>
                        ))}
                    </div>
                </section>
            ) : (
                <section className="kodem-reveal max-w-6xl mx-auto px-6 pb-32">
                    <SectionLabel>EN COURS</SectionLabel>
                    <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">Première note en préparation</h2>
                    <p className="mt-4 max-w-2xl text-lg text-acier">
                        Cette section publiera bientôt ses premières notes de fond.
                    </p>
                </section>
            )}
        </PublicLayout>
    );
}
