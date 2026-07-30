import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import CaseStudy from '@/Components/CaseStudy';
import CaseImage from '@/Components/CaseImage';
import { TestimonialSection } from '@/Components/Testimonial';
import ContactBlockMono from '@/Components/ContactBlockMono';

export default function RealisationShow({ meta, cas, testimonials = [] }) {
    return (
        <PublicLayout meta={meta}>
            <div className="max-w-6xl mx-auto px-6 pt-8">
                {/* Visual breadcrumb */}
                <nav aria-label="Fil d'Ariane" className="flex items-center gap-2 font-mono text-legende text-acier mb-8">
                    <Link href="/" className="hover:text-cobalt-600 transition">Accueil</Link>
                    <span aria-hidden="true">/</span>
                    <Link href="/realisations" className="hover:text-cobalt-600 transition">Réalisations</Link>
                    <span aria-hidden="true">/</span>
                    <span className="text-encre" aria-current="page">{cas?.titre}</span>
                </nav>

                {/* Case title */}
                <h1 className="text-kodem-h1 md:text-display font-bold text-encre leading-tight mb-4 max-w-3xl">
                    {cas?.titre}
                </h1>

                {cas?.resume && (
                    <p className="animate-kodem-slide text-lg text-acier max-w-2xl mb-12">{cas.resume}</p>
                )}
            </div>

            {/* Encart visuel du cas — image réelle ou réserve d'image charte */}
            <div className="max-w-6xl mx-auto px-6 mb-12">
                <CaseImage cas={cas} variant="hero" />
            </div>

            {/* Case study content */}
            <div className="kodem-reveal max-w-6xl mx-auto px-6 pb-16">
                <CaseStudy cas={cas} />

                {/* Testimonials for this case */}
                <TestimonialSection items={testimonials} />

                {/* CTA */}
                <div className="mt-16 grid md:grid-cols-2 gap-8 items-start">
                    <div>
                        <p className="text-lg font-medium text-encre mb-2">
                            Un projet similaire à déployer ?
                        </p>
                        <p className="text-acier text-sm mb-6">
                            Décrivez votre besoin — matériel, lieu, contraintes — et nous vous répondons sous 48 h.
                        </p>
                        <Link
                            href="/contact"
                            onClick={() => trackClick('case_cta_contact', { slug: cas?.slug })}
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
