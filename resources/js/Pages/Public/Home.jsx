import { useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import '../../../css/home.css';

/**
 * Une ligne de code : [seuil d'apparition (0 à 1), segments, optionnelle].
 * Un segment est soit du texte brut, soit [classe, texte] avec la classe parmi
 * k (mot clé), s (chaîne), c (commentaire), ok (succès), cursor (curseur).
 * Les lignes optionnelles sont masquées sur les petits écrans peu hauts.
 *
 * Chaque « used » ne cite qu'une preuve de la liste fermée du directeur
 * (offres.md, « Preuves mobilisables ») : aucune autre preuve ici.
 */
const SKILLS = [
    {
        meta: '// compétence 01 · conception de bout en bout',
        word: 'conception',
        desc: "Base de données, règles métier et interface : l'outil est conçu de zéro, puis mené jusqu'à la mise en production par un seul interlocuteur.",
        tags: ['Base de données', 'Règles métier', 'Interface web', 'API REST', 'Laravel, React ou Vue.js'],
        used: (
            <>
                un outil de mises en service <b>conçu de zéro</b>, livré de 2024 à 2025
            </>
        ),
        file: 'routes/web.php',
        lines: [
            [0.05, [['k', 'Route'], '::middleware(', ['s', "'auth'"], ')']],
            [0.12, ['  ->prefix(', ['s', "'mises-en-service'"], ')']],
            [0.19, ['  ->group(', ['k', 'function'], ' () {']],
            [0.26, ['    Route::get(', ['s', "'/'"], ', [MiseEnServiceController::', ['k', 'class'], ', ', ['s', "'index'"], ']);']],
            [0.33, ['    Route::post(', ['s', "'{client}/lancer'"], ',']],
            [0.4, ['        [MiseEnServiceController::', ['k', 'class'], ', ', ['s', "'lancer'"], ']);']],
            [0.47, ['  });']],
            [0.54, [' '], true],
            [0.58, [['c', '// une règle métier, un écran, une route']], true],
        ],
    },
    {
        variant: 'alt',
        meta: '// compétence 02 · mises en service automatisées',
        word: 'automatiser',
        desc: "Attribution DHCP automatique et génération des configurations VPN : la mise en service d'un client ne passe plus par la main, ni par une saisie qui peut couper un service.",
        tags: ['Attribution DHCP', 'Configurations VPN', 'Génération de fichiers', 'Règles écrites avant le code'],
        used: (
            <>
                mises en service <b>qui ne passent plus par la main</b>
            </>
        ),
        file: 'app/Actions/MettreEnService.php',
        lines: [
            [0.05, [['k', 'public function'], ' __invoke(Client $client): ', ['k', 'void']]],
            [0.12, ['{']],
            [0.19, ['    $bail = $this->dhcp->attribuer($client);']],
            [0.26, ['    $config = $this->vpn->generer($client, $bail);']],
            [0.33, [' '], true],
            [0.38, ['    Storage::put(', ['s', '"vpn/{$client->code}.conf"'], ', $config);']],
            [0.45, ['}']],
            [0.52, [' '], true],
            [0.58, [['ok', '✓ client 0412 en service'], '  ', ['c', '· 0 saisie manuelle']], true],
        ],
    },
    {
        meta: '// compétence 03 · qualité & mise en production',
        word: 'qualité',
        desc: "Tests unitaires, fonctionnels et de bout en bout, analyse statique, intégration continue, puis un déploiement automatique en préproduction et en production.",
        tags: ['PHPUnit', 'Tests de bout en bout', 'PHPStan / Larastan', 'GitHub Actions', 'Docker Compose'],
        used: (
            <>
                <b>619 tests automatisés</b> sur une application en production, avec intégration continue et analyse statique
            </>
        ),
        file: 'github actions · laravel ci',
        lines: [
            [0.05, [['ok', '✓'], ' composer install']],
            [0.12, [['ok', '✓'], ' php artisan migrate']],
            [0.19, [['ok', '✓'], ' phpstan analyse']],
            [0.26, [['ok', '✓'], ' Tests\\Unit']],
            [0.33, [['ok', '✓'], ' Tests\\Feature']],
            [0.4, [['ok', '✓'], ' Tests\\E2E']],
            [0.47, [' '], true],
            [0.54, [['k', '→'], ' deploy preprod … ', ['ok', 'ok']]],
            [0.61, [['k', '→'], ' deploy production … ', ['ok', 'ok'], ' ', ['cursor', '']]],
        ],
    },
    {
        variant: 'alt',
        meta: '// compétence 04 · exploitation & maintenance',
        word: 'exploitation',
        desc: "Déploiement automatisé, sauvegardes chiffrées, corrections et évolutions après la livraison : l'outil reste en service sans régression, et le code source appartient au client.",
        tags: ['Déploiement automatisé', 'Sauvegardes chiffrées', 'Tâches planifiées', 'Corrections et évolutions'],
        used: (
            <>
                SaaS en production, <b>30 abonnements payants</b>
            </>
        ),
        file: 'routes/console.php',
        lines: [
            [0.05, [['c', '// ce qui tourne sans personne']]],
            [0.12, ['Schedule::command(', ['s', "'backup:run'"], ')->dailyAt(', ['s', "'02:30'"], ');']],
            [0.19, ['Schedule::command(', ['s', "'backup:clean'"], ')->daily();']],
            [0.26, ['Schedule::command(', ['s', "'queue:prune-failed'"], ')->weekly();']],
            [0.33, [' '], true],
            [0.4, [['c', '// chaque correction repasse par la CI']]],
            [0.47, [['k', '→'], ' release v1.12.3 … ', ['ok', 'ok']]],
        ],
    },
    {
        variant: 'dark',
        meta: '// compétence 05 · diagnostic en production',
        word: 'diagnostic',
        desc: "Quand ça casse en production, je pars des données. Six ans de support sur un parc de sites de vente en ligne m'ont appris à écrire la requête qui trouve la cause, pas à deviner.",
        tags: ["SQL d'investigation", 'Procédures stockées', "Gestion d'incidents", 'Windows Server', 'Réseau, DHCP, VPN'],
        used: (
            <>
                6 ans de <b>diagnostic SQL en production</b> avant le développement
            </>
        ),
        file: 'incident.sql',
        lines: [
            [0.05, [['c', '-- commandes validées sans billet émis']]],
            [0.12, [['k', 'SELECT'], ' o.id, o.created_at, o.site_id']],
            [0.19, [['k', 'FROM'], ' orders o']],
            [0.26, [['k', 'LEFT JOIN'], ' tickets t ', ['k', 'ON'], ' t.order_id = o.id']],
            [0.33, [['k', 'WHERE'], ' o.status = ', ['s', "'validated'"]]],
            [0.4, ['  ', ['k', 'AND'], ' t.id ', ['k', 'IS NULL']]],
            [0.47, [['k', 'ORDER BY'], ' o.created_at ', ['k', 'DESC'], ';']],
            [0.54, [' '], true],
            [0.61, [['c', '-- cause trouvée, correctif livré'], ' ', ['cursor', '']]],
        ],
    },
];

/** Libellé de code affiché sur chaque carte, dans l'ordre des capacités puis des secteurs. */
const OFFER_ACTIONS = ['action(concevoir)', 'action(exploiter)', 'action(renforcer)'];

/** Frise alignée sur les profils publics (export LinkedIn du 2025-09-24). */
const TIMELINE = [
    { date: '2017 à 2023', text: 'Technicien support fonctionnel chez Vivaticket, sites de billetterie en production', key: true },
    { date: '2021', text: 'Développeur professionnel : premières missions Laravel et Symfony' },
    { date: '2022', text: 'Titre pro développeur web et web mobile' },
    { date: '2024', text: 'Développeur en CDI chez PICTAV Informatique, de février 2024 à janvier 2025' },
    { date: '2024 à 2025', text: 'Outil de mises en service conçu de zéro pour MUXEN, de janvier 2024 à septembre 2025', key: true },
    { date: "aujourd'hui", text: 'KODEM : outils métier sur mesure, de la base de données à la mise en production' },
];

const pad = (n) => String(n).padStart(2, '0');

export default function Home({ meta, positioning = {} }) {
    const capabilities = positioning.capabilities || [];
    const secteurs = positioning.secteurs || [];
    const offers = [
        ...capabilities.map((c) => ({ title: c.titre, text: c.role_support })),
        ...secteurs.map((s) => ({ title: s.nom, text: s.processus })),
    ].map((offer, i) => ({ ...offer, action: OFFER_ACTIONS[i] || 'action()' }));
    const situations = positioning.probleme?.situations || [];
    const cta = positioning.cta_primary || { label: 'Décrivez la tâche que vous voulez supprimer', href: '/contact' };

    return (
        <PublicLayout meta={meta}>
            <div className="kh">
                <section className="kh-intro">
                    <p className="kh-eyebrow">// mathieu siaudeau · KODEM · outils métier sur mesure · poitiers</p>
                    <h1>
                        Des outils métier sur mesure qui suppriment vos <b>[</b>tâches manuelles<b>]</b>.
                    </h1>
                    <p className="kh-lead">{positioning.hero_baseline}</p>
                    <p className="kh-lead">
                        <Link
                            href={cta.href}
                            className="kh-btn"
                            onClick={() => trackClick('hero_cta_contact', { location: 'home_hero' })}
                        >
                            {cta.label}
                        </Link>
                    </p>
                    <span className="kh-go">
                        <i />
                        scrollez pour parcourir {SKILLS.length} compétences
                    </span>
                </section>

                <SkillSequence />

                <section className="kh-block" id="missions">
                    <p className="kh-eyebrow">// missions</p>
                    <h2>{positioning.probleme?.titre || 'Des tâches qui passent encore par la main'}</h2>
                    {situations.length > 0 && (
                        <ul className="kh-path-intro">
                            {situations.map((situation) => (
                                <li key={situation}>{situation}</li>
                            ))}
                        </ul>
                    )}
                    <div className="kh-offers">
                        {offers.map((offer) => (
                            <article key={offer.action} className="kh-offer">
                                <code>{offer.action}</code>
                                <h3>{offer.title}</h3>
                                <p>{offer.text}</p>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="kh-block kh-path" id="parcours">
                    <p className="kh-eyebrow">// parcours</p>
                    <h2>J'ai réparé la production avant de la coder.</h2>
                    <p className="kh-path-intro">
                        Six ans au support fonctionnel d'un parc de sites de billetterie, à chercher dans les bases
                        pourquoi une commande avait échoué. C'est pour ça que chaque outil livré par KODEM part en
                        production avec ses tests.
                    </p>
                    <ol className="kh-tl">
                        {TIMELINE.map((step) => (
                            <li key={step.date} className={step.key ? 'kh-tl--key' : undefined}>
                                <time>{step.date}</time>
                                <p>{step.text}</p>
                            </li>
                        ))}
                    </ol>
                </section>

                <HomeContact ctaFinal={positioning.cta_final} />
            </div>
        </PublicLayout>
    );
}

/**
 * Séquence collante : la section fait plusieurs écrans de haut, la scène reste
 * fixée sous l'en-tête et chaque calque recouvre le précédent en biais selon
 * la progression du scroll. Les styles sont écrits directement sur le DOM, hors
 * du rendu React : une image par frame, aucun re-rendu.
 */
function SkillSequence() {
    const seqRef = useRef(null);
    const stageRef = useRef(null);
    const boxRef = useRef(null);
    const edgeRef = useRef(null);
    const curRef = useRef(null);

    useEffect(() => {
        const seq = seqRef.current;
        const stage = stageRef.current;
        const box = boxRef.current;
        const edge = edgeRef.current;
        const cur = curRef.current;
        const layers = Array.from(box.querySelectorAll('.kh-layer'));
        const fills = Array.from(seq.querySelectorAll('.kh-segs i'));
        const header = document.querySelector('header');
        const N = layers.length;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const HOLD = 0.3;
        const SPAN = 1.5;
        let W = 0;
        let H = 0;
        let skew = 0;
        let frame = 0;

        const clamp = (v, a, b) => (v < a ? a : v > b ? b : v);
        const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
        const enter = (p, k) => {
            const t = clamp((p - (k - 1) - HOLD) / (1 - HOLD), 0, 1);
            return reduce ? (t >= 0.5 ? 1 : 0) : ease(t);
        };

        function size() {
            if (header) seq.style.setProperty('--kh-head', `${header.offsetHeight}px`);
            H = stage.clientHeight;
            W = box.clientWidth;
            seq.style.height = `${Math.round(H * ((N - 1) * SPAN + 1))}px`;
            skew = (-Math.atan((0.25 * W) / H) * 180) / Math.PI;
        }

        function render() {
            frame = 0;
            const top = seq.getBoundingClientRect().top;
            const stickTop = parseFloat(getComputedStyle(stage).top) || 0;
            const max = seq.offsetHeight - H;
            const p = max > 0 ? clamp((stickTop - top) / max, 0, 1) * (N - 1) : 0;
            let live = -1;
            let active = 0;

            layers.forEach((layer, k) => {
                const i = k === 0 ? 1 : enter(p, k);
                const o = k === N - 1 ? 0 : enter(p, k + 1);
                layer.style.setProperty('--in', i.toFixed(4));
                layer.style.setProperty('--out', reduce ? '0' : o.toFixed(4));
                if (k > 0) {
                    const s = i * 125;
                    layer.style.clipPath = i >= 1 ? 'none' : `polygon(0 0,${s}% 0,${s - 25}% 100%,0 100%)`;
                    if (i > 0 && i < 1) live = i;
                }
                layer.style.visibility = (k > 0 && i <= 0) || o >= 1 ? 'hidden' : 'visible';
                if (i >= 0.5) active = k;
                fills[k].style.transform = `scaleX(${k === 0 ? 1 : clamp(p - (k - 1), 0, 1)})`;
            });

            if (live > 0 && !reduce) {
                edge.style.opacity = '1';
                edge.style.transform = `translateX(${live * 1.25 * W}px) skewX(${skew}deg)`;
            } else {
                edge.style.opacity = '0';
            }
            cur.textContent = pad(active + 1);
        }

        const request = () => {
            if (!frame) frame = requestAnimationFrame(render);
        };
        const onResize = () => {
            size();
            request();
        };

        window.addEventListener('scroll', request, { passive: true });
        window.addEventListener('resize', onResize);
        size();
        render();

        return () => {
            window.removeEventListener('scroll', request);
            window.removeEventListener('resize', onResize);
            if (frame) cancelAnimationFrame(frame);
        };
    }, []);

    return (
        <section className="kh-seq" id="competences" aria-label="Compétences" ref={seqRef}>
            <div className="kh-stage" ref={stageRef}>
                <div className="kh-layers" ref={boxRef}>
                    {SKILLS.map((skill, index) => (
                        <SkillLayer key={skill.word} skill={skill} number={pad(index + 1)} />
                    ))}
                    <span className="kh-edge" aria-hidden="true" ref={edgeRef} />
                </div>
                <div className="kh-rail" aria-hidden="true">
                    <span>
                        <span className="kh-cur" ref={curRef}>
                            01
                        </span>{' '}
                        / {pad(SKILLS.length)}
                    </span>
                    <span className="kh-segs">
                        {SKILLS.map((skill) => (
                            <span key={skill.word}>
                                <i />
                            </span>
                        ))}
                    </span>
                </div>
            </div>
        </section>
    );
}

function SkillLayer({ skill, number }) {
    return (
        <article className={`kh-layer${skill.variant ? ` kh-layer--${skill.variant}` : ''}`}>
            <span className="kh-num" aria-hidden="true">
                {number}
            </span>
            <div className="kh-txt">
                <p className="kh-meta">{skill.meta}</p>
                <h2 className="kh-ttl">
                    <span className="kh-br kh-br--l" aria-hidden="true">
                        [
                    </span>
                    <span className="kh-w">{skill.word}</span>
                    <span className="kh-br kh-br--r" aria-hidden="true">
                        ]
                    </span>
                </h2>
                <p className="kh-desc">{skill.desc}</p>
                <ul className="kh-tags">
                    {skill.tags.map((tag) => (
                        <li key={tag}>{tag}</li>
                    ))}
                </ul>
                <p className="kh-used">{skill.used}</p>
            </div>
            <div className="kh-fig" aria-hidden="true">
                <div className="kh-code">
                    <div className="kh-bar">
                        <i />
                        <i />
                        <i />
                        <span>{skill.file}</span>
                    </div>
                    <pre>
                        {skill.lines.map(([threshold, segments, optional]) => (
                            <span
                                key={threshold}
                                className={`kh-ln${optional ? ' kh-ln--opt' : ''}`}
                                style={{ '--t': threshold }}
                            >
                                {segments.map((segment, i) =>
                                    typeof segment === 'string' ? (
                                        segment
                                    ) : (
                                        <span key={i} className={`kh-${segment[0]}`}>
                                            {segment[1]}
                                        </span>
                                    ),
                                )}
                            </span>
                        ))}
                    </pre>
                </div>
            </div>
        </article>
    );
}

function HomeContact({ ctaFinal }) {
    const { contactEmail } = usePage().props;
    const mailRef = useRef(null);
    const [label, setLabel] = useState("Copier l'adresse");

    function selectAddress() {
        const range = document.createRange();
        range.selectNodeContents(mailRef.current);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        setLabel('Adresse sélectionnée');
    }

    function copyAddress() {
        trackClick('home_copy_email');
        try {
            navigator.clipboard.writeText(contactEmail).then(() => setLabel('Adresse copiée'), selectAddress);
        } catch {
            // Presse papiers indisponible (contexte non sécurisé) : on sélectionne l'adresse à la place.
            selectAddress();
        }
    }

    return (
        <section className="kh-outro" id="contact">
            <p className="kh-eyebrow">// contact</p>
            <h2>
                Une tâche manuelle à <b>[</b>supprimer<b>]</b> ?
            </h2>
            <p>{ctaFinal?.texte || 'Décrivez la tâche, les logiciels concernés et sa fréquence : réponse sous 48 h.'}</p>
            <div className="kh-contact">
                <span className="kh-mail" ref={mailRef}>
                    {contactEmail}
                </span>
                <button className="kh-btn" type="button" onClick={copyAddress}>
                    {label}
                </button>
            </div>
            <p className="kh-tel">07 62 61 26 46 · Poitiers · missions à distance</p>
        </section>
    );
}
