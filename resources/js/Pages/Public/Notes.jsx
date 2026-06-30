import PublicLayout from '@/Layouts/PublicLayout';
import Banner from '@/Components/Banner';
import SectionLabel from '@/Components/SectionLabel';

export default function Notes({ meta }) {
    return (
        <PublicLayout meta={meta}>
            <Banner title="Notes techniques" />

            <section className="max-w-6xl mx-auto px-6 pt-16 pb-32">
                <SectionLabel>EN COURS</SectionLabel>
                <h2 className="mt-4 text-kodem-h1 font-bold max-w-2xl">
                    Notes techniques — à venir
                </h2>
                <p className="mt-4 max-w-2xl text-lg text-acier">
                    Cette section publie des notes de fond sur les dispositifs connectés sur site :
                    choix techniques, retours d'expérience, compromis documentés.
                    Aucun guide générique — uniquement des sujets où KODEM a une position à défendre.
                </p>
                <p className="mt-4 text-acier">
                    Première note en préparation.
                </p>
            </section>
        </PublicLayout>
    );
}
