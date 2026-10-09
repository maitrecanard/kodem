import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { route as ziggyRoute } from '../../vendor/tightenco/ziggy';
import pageTitle from './lib/pageTitle';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => pageTitle(title, appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        // route() global, construit depuis la prop partagée « ziggy » et non depuis un script en
        // ligne (bloqué par le CSP). « location » est retirée : elle ne vaut que pour le rendu
        // serveur, le navigateur doit lire l'adresse courante pour route().current().
        const { location: _serverLocation, ...ziggy } = props.initialPage.props.ziggy;
        window.route = (name, params, absolute) => ziggyRoute(name, params, absolute, ziggy);

        if (import.meta.env.SSR) {
            hydrateRoot(el, <App {...props} />);
            return;
        }

        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: '#2348E0', // Cobalt — couleur primaire de la marque
    },
});
