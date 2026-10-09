import { useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { trackClick } from '@/lib/track';
import '../../../css/home.css';

const CHIPS = [
    { label: 'Laravel', key: true },
    { label: 'Symfony', key: true },
    { label: 'PHP 8' },
    { label: 'API REST' },
    { label: 'MySQL' },
    { label: 'Stripe' },
    { label: 'React' },
    { label: 'Vue.js' },
    { label: 'PHPUnit' },
    { label: 'GitHub Actions' },
    { label: 'Docker' },
    { label: 'Reverb' },
];

/**
 * Une ligne de code : [seuil d'apparition (0 à 1), segments, optionnelle].
 * Un segment est soit du texte brut, soit [classe, texte] avec la classe parmi
 * k (mot clé), s (chaîne), c (commentaire), ok (succès), cursor (curseur).
 * Les lignes optionnelles sont masquées sur les petits écrans peu hauts.
 */
const SKILLS = [
    {
        meta: '// compétence 01 · backend & api',
        word: 'backend',
        desc: "Je conçois la base de données, l'authentification, la logique métier et l'API, puis l'espace d'administration. Laravel ou Symfony selon votre existant.",
        tags: ['Laravel 12', 'Symfony', 'API REST publique', 'MySQL', 'SQL Server'],
        used: (
            <>
                en production sur <b>un SaaS</b> et <b>le backend d'une app mobile</b>
            </>
        ),
        file: 'routes/api.php',
        lines: [
            [0.05, [['k', 'Route'], '::middleware(', ['s', "'auth:sanctum'"], ')']],
            [0.12, ['  ->prefix(', ['s', "'v1'"], ')']],
            [0.19, ['  ->group(', ['k', 'function'], ' () {']],
            [0.26, ['    Route::apiResource(', ['s', "'projects'"], ', ProjectController::', ['k', 'class'], ');']],
            [0.33, ['    Route::post(', ['s', "'projects/{project}/publish'"], ',']],
            [0.4, ['        [ProjectController::', ['k', 'class'], ', ', ['s', "'publish'"], ']);']],
            [0.47, ['  });']],
            [0.54, [' '], true],
            [0.58, [['c', '// GET  /api/v1/projects        200']], true],
            [0.62, [['c', '// POST /api/v1/projects/42/publish  201']], true],
        ],
    },
    {
        variant: 'alt',
        meta: '// compétence 02 · paiement en ligne',
        word: 'paiement',
        desc: "Abonnements, facturation, remboursements, et marketplaces où l'argent est reversé automatiquement aux prestataires. Aucune donnée de carte ne passe par vos serveurs.",
        tags: ['Stripe Billing', 'Stripe Connect Express', 'Webhooks', "QR code d'accès"],
        used: (
            <>
                en production sur <b>un SaaS par abonnement</b> et <b>une marketplace mobile</b>
            </>
        ),
        file: 'StripeWebhookController.php',
        lines: [
            [0.05, [['k', 'match'], ' ($event->type) {']],
            [0.12, ['  ', ['s', "'invoice.paid'"], ' =>']],
            [0.19, ['      $subscriptions->activate($event),']],
            [0.26, ['  ', ['s', "'charge.refunded'"], ' =>']],
            [0.33, ['      $subscriptions->refund($event),']],
            [0.4, ['  ', ['s', "'transfer.created'"], ' =>']],
            [0.47, ['      $payouts->confirm($event),']],
            [0.54, ['  ', ['k', 'default'], ' => ', ['k', 'null'], ','], true],
            [0.6, ['};']],
        ],
    },
    {
        meta: "// compétence 03 · contrôle d'accès",
        word: 'accès',
        desc: "Authentification, rôles et permissions, accès réservé aux abonnés actifs, contrôle sur place par QR code. Chaque utilisateur voit ce qu'il a le droit de voir, rien de plus.",
        tags: ['Authentification', 'Rôles & permissions', 'Policies & middlewares', 'Accès par abonnement', 'QR code'],
        used: (
            <>
                contrôle d'abonnement <b>par QR code</b> en production
            </>
        ),
        file: 'VerifyPassController.php',
        lines: [
            [0.05, [['c', "// scan du QR code à l'entrée"]]],
            [0.12, [['k', 'public function'], ' __invoke(Member $member)']],
            [0.19, ['{']],
            [0.26, ['    $this->authorize(', ['s', "'scan'"], ', $member);']],
            [0.31, [' '], true],
            [0.36, ['    ', ['k', 'return'], ' $member->subscription?->active()']],
            [0.43, ['        ? response()->json([', ['s', "'access'"], ' => ', ['s', "'granted'"], '])']],
            [0.5, ['        : response()->json([', ['s', "'access'"], ' => ', ['s', "'denied'"], '], ', ['k', '403'], ');']],
            [0.57, ['}']],
            [0.62, [['ok', '✓ 200 granted'], '  ', ['c', '· abonné actif']], true],
        ],
    },
    {
        variant: 'alt',
        meta: '// compétence 04 · qualité & mise en production',
        word: 'qualité',
        desc: "Tests unitaires, d'intégration et de bout en bout, analyse statique, intégration continue, puis un déploiement automatique en préproduction et en production.",
        tags: ['PHPUnit', 'Tests E2E', 'PHPStan / Larastan', 'GitHub Actions', 'Docker Compose'],
        used: (
            <>
                chaque mise en ligne passe par <b>les tests et la CI</b>, paiement compris
            </>
        ),
        file: 'github actions · laravel ci',
        lines: [
            [0.05, [['ok', '✓'], ' composer install']],
            [0.12, [['ok', '✓'], ' php artisan migrate']],
            [0.19, [['ok', '✓'], ' phpstan analyse']],
            [0.26, [['ok', '✓'], ' Tests\\Unit']],
            [0.33, [['ok', '✓'], ' Tests\\Feature\\Stripe\\StripeWebhookRefundTest']],
            [0.4, [['ok', '✓'], ' Tests\\E2E\\UserJourneyTest']],
            [0.47, [' '], true],
            [0.54, [['k', '→'], ' deploy preprod … ', ['ok', 'ok']]],
            [0.61, [['k', '→'], ' deploy production … ', ['ok', 'ok'], ' ', ['cursor', '']]],
        ],
    },
    {
        meta: '// compétence 05 · temps réel & intégrations',
        word: 'temps réel',
        desc: "Messages instantanés et mises à jour en direct via WebSockets avec Laravel Reverb, hébergé sur votre serveur. S'y ajoutent les notifications push, les e‑mails planifiés et les bots connectés à votre API.",
        tags: ['Laravel Reverb', 'Broadcasting', 'Laravel Echo', 'Expo Notifications', 'Tâches planifiées'],
        used: (
            <>
                WebSockets <b>auto‑hébergés</b>, sans service tiers facturé au message
            </>
        ),
        file: 'app/Events/MessageSent.php · reverb',
        lines: [
            [0.05, [['k', 'class'], ' MessageSent ', ['k', 'implements'], ' ShouldBroadcast']],
            [0.12, ['{']],
            [0.19, ['    ', ['k', 'public function'], ' broadcastOn(): ', ['k', 'array']]],
            [0.26, ['    {']],
            [0.33, ['        ', ['k', 'return'], ' [', ['k', 'new'], ' PrivateChannel(', ['s', '"chat.{$this->roomId}"'], ')];']],
            [0.4, ['    }']],
            [0.47, ['}']],
            [0.52, [' '], true],
            [0.57, [['c', '// front : Echo.private(`chat.${roomId}`)']]],
            [0.62, [['c', "//   .listen('MessageSent', render)"]]],
        ],
    },
    {
        variant: 'dark',
        meta: '// compétence 06 · diagnostic en production',
        word: 'diagnostic',
        desc: "Quand ça casse en production, je pars des données. Six ans de support sur un parc de sites e‑commerce m'ont appris à écrire la requête qui trouve la cause, pas à deviner.",
        tags: ["SQL d'investigation", 'Procédures stockées', "Gestion d'incidents", 'Windows Server', 'Réseau, DHCP, VPN'],
        used: (
            <>
                6 ans de <b>support en production</b> avant le développement
            </>
        ),
        file: 'incident.sql',
        lines: [
            [0.05, [['c', '-- commandes payées sans billet émis']]],
            [0.12, [['k', 'SELECT'], ' o.id, o.paid_at, o.site_id']],
            [0.19, [['k', 'FROM'], ' orders o']],
            [0.26, [['k', 'LEFT JOIN'], ' tickets t ', ['k', 'ON'], ' t.order_id = o.id']],
            [0.33, [['k', 'WHERE'], ' o.status = ', ['s', "'paid'"]]],
            [0.4, ['  ', ['k', 'AND'], ' t.id ', ['k', 'IS NULL']]],
            [0.47, [['k', 'ORDER BY'], ' o.paid_at ', ['k', 'DESC'], ';']],
            [0.54, [' '], true],
            [0.61, [['c', '-- cause trouvée, correctif livré'], ' ', ['cursor', '']]],
        ],
    },
];

const OFFERS = [
    {
        action: 'action(construire)',
        title: 'Construire un backend',
        text: "Vous avez un produit à lancer. Je conçois et livre le backend, l'API et le paiement, testés et déployés.",
        points: ['architecture et base de données', 'API documentée', 'déploiement préprod et prod'],
    },
    {
        action: 'action(encaisser)',
        title: 'Ajouter le paiement',
        text: "Votre application existe et doit facturer. J'intègre Stripe, abonnements ou marketplace, avec des webhooks couverts par des tests.",
        points: ['abonnements et factures', 'reversements aux prestataires', 'remboursements testés'],
    },
    {
        action: 'action(fiabiliser)',
        title: 'Reprendre une application',
        text: 'Votre application casse ou fait peur à chaque mise en ligne. Je diagnostique, je pose les tests et une intégration continue.',
        points: ['audit et diagnostic', 'tests et analyse statique', 'mises en ligne sans stress'],
    },
];

const TIMELINE = [
    { date: '2016', text: "Technicien d'assistance informatique, AFPA" },
    { date: '2017–23', text: 'Support de sites e‑commerce en production', key: true },
    { date: '2021', text: 'Premières missions freelance, Laravel et Symfony' },
    { date: '2022', text: 'Titre pro développeur web et web mobile' },
    { date: '2023', text: 'SaaS santé, en équipe de 10 développeurs' },
    { date: '2024 →', text: 'Freelance : SaaS, backend mobile, produit réseau', key: true },
];

const pad = (n) => String(n).padStart(2, '0');

export default function Home({ meta }) {
    return (
        <PublicLayout meta={meta}>
            <div className="kh">
                <section className="kh-intro">
                    <p className="kh-eyebrow">// mathieu siaudeau · développeur backend php · freelance · poitiers</p>
                    <h1>
                        Je construis le <b>[</b>backend<b>]</b> de votre produit.
                    </h1>
                    <p className="kh-lead">
                        API, paiements, temps réel, tests et mise en production, en Laravel ou en Symfony. Je prends le
                        projet seul, de l'architecture au déploiement, et je travaille à distance.
                    </p>
                    <ul className="kh-chips" aria-label="Technologies">
                        {CHIPS.map((chip) => (
                            <li key={chip.label} className={chip.key ? 'kh-chip--key' : undefined}>
                                {chip.label}
                            </li>
                        ))}
                    </ul>
                    <span className="kh-go">
                        <i />
                        scrollez pour parcourir {SKILLS.length} compétences
                    </span>
                </section>

                <SkillSequence />

                <section className="kh-block" id="missions">
                    <p className="kh-eyebrow">// missions</p>
                    <h2>Trois façons de travailler ensemble.</h2>
                    <div className="kh-offers">
                        {OFFERS.map((offer) => (
                            <article key={offer.action} className="kh-offer">
                                <code>{offer.action}</code>
                                <h3>{offer.title}</h3>
                                <p>{offer.text}</p>
                                <ul>
                                    {offer.points.map((point) => (
                                        <li key={point}>{point}</li>
                                    ))}
                                </ul>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="kh-block kh-path" id="parcours">
                    <p className="kh-eyebrow">// parcours</p>
                    <h2>J'ai réparé la production avant de la coder.</h2>
                    <p className="kh-path-intro">
                        Six ans au support d'un parc de sites de billetterie, à chercher dans les bases pourquoi une
                        commande avait échoué. C'est pour ça que je teste tout ce qui touche à l'argent.
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

                <HomeContact />
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

function HomeContact() {
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
                Un backend à <b>[</b>construire<b>]</b> ou à reprendre ?
            </h2>
            <p>
                Décrivez le produit, ce qui existe déjà et ce qui bloque. Je réponds avec une approche technique et un
                ordre de prix.
            </p>
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
