import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Configuration Tailwind — Charte graphique KODEM (V1.0 — 2026)
 *
 * Palette de marque :
 *   Cobalt      #2348E0  — couleur primaire / accent (liens, boutons, données clés)
 *   Indigo nuit #0B1B4D  — fonds sombres & texte (alias « encre »)
 *   Brume       #E8ECFF  — bordures, séparateurs, surfaces très claires
 *   Acier       #64748B  — texte secondaire, métadonnées
 *   Papier      #FBFCFE  — fond clair dominant (≈ 60 % de la surface)
 *
 * Le scale `indigo` natif de Tailwind est remappé sur la rampe cobalt :
 * toutes les classes `*-indigo-*` déjà présentes dans l'app deviennent
 * automatiquement « cobalt », sans édition page par page.
 *
 * Typographie :
 *   Space Grotesk — titrage & texte (font-sans)
 *   JetBrains Mono — libellés, code, données (font-mono)
 */

// Rampe cobalt : 600 = cobalt primaire, 950 = indigo nuit (encre).
const cobalt = {
    50: '#EEF2FE',
    100: '#E8ECFF', // Brume
    200: '#C7D2FB',
    300: '#9DB0F7',
    400: '#5B79EE',
    500: '#3457E6',
    600: '#2348E0', // Cobalt — primaire
    700: '#1C3AB8',
    800: '#182F93',
    900: '#16266F',
    950: '#0B1B4D', // Indigo nuit — encre
};

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Tokens sémantiques de la charte
                cobalt: cobalt,
                encre: '#0B1B4D',
                brume: '#E8ECFF',
                acier: '#64748B',
                papier: '#FBFCFE',

                // Remap : tout `indigo-*` existant bascule sur la rampe cobalt.
                indigo: cobalt,
            },

            fontSize: {
                // Échelle typographique KODEM (page 08 de la charte)
                legende: ['0.6875rem', { lineHeight: '1.5' }], // 11px — métadonnée / annotation
                display: ['3.5rem', { lineHeight: '1.05', letterSpacing: '-0.02em' }], // 56px — Titre
                'kodem-h1': ['2.125rem', { lineHeight: '1.15', letterSpacing: '-0.01em' }], // 34px
                'kodem-h2': ['1.5rem', { lineHeight: '1.25' }], // 24px
            },

            borderRadius: {
                kodem: '0.625rem', // 10px — rayon de carte standard
            },

            /**
             * Primitives d'animation KODEM.
             * L'état MASQUÉ n'existe QUE dans les @keyframes : si les animations
             * CSS ne s'appliquent pas (moteur sans support, CSS non chargé), aucune
             * règle statique ne pose opacity:0 → le contenu reste VISIBLE.
             * Seules `transform` et `opacity` sont animées : zéro reflow, zéro CLS.
             */
            keyframes: {
                'kodem-fade': {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                'kodem-rise': {
                    from: { opacity: '0', transform: 'translateY(var(--kodem-rise))' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                /**
                 * Variante SANS opacité, réservée au contenu au-dessus de la ligne
                 * de flottaison des pages sans bannière. Mesuré au Lighthouse le
                 * 2026-07-30 sur /contact : un bloc de texte animé avec `kodem-rise`
                 * (qui part d'opacity:0) retarde le LCP de 4,2 s contre 3,6 s sans
                 * animation — Chrome ne retient pas un élément peint à opacité nulle
                 * comme candidat LCP. Un élément seulement translaté reste peint,
                 * donc éligible : le mouvement est conservé, le LCP ne bouge pas.
                 */
                'kodem-slide': {
                    from: { transform: 'translateY(var(--kodem-rise))' },
                    to: { transform: 'translateY(0)' },
                },
            },

            animation: {
                'kodem-fade': 'kodem-fade var(--kodem-dur-3) var(--kodem-ease) both',
                'kodem-rise': 'kodem-rise var(--kodem-dur-3) var(--kodem-ease) both',
                'kodem-slide': 'kodem-slide var(--kodem-dur-3) var(--kodem-ease) both',
            },
        },
    },

    plugins: [forms],
};
