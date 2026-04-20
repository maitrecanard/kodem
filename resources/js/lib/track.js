/**
 * Client tracking "fire-and-forget". Ne bloque jamais l'UI.
 * Utilise sendBeacon quand disponible pour survivre aux navigations.
 *
 * Usage :
 *   import { track, trackClick } from '@/lib/track';
 *   trackClick('hero_cta_audit', { location: 'home' });
 *   track('form.submitted', 'contact', { subject_len: 42 });
 */

function csrfToken() {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
}

export function track(type, name, metadata = {}) {
    if (!type || !name) return;
    try {
        const body = JSON.stringify({ type, name, metadata });

        if (typeof navigator !== 'undefined' && navigator.sendBeacon) {
            const blob = new Blob([body], { type: 'application/json' });
            // sendBeacon ne permet pas de headers custom, on ajoute le token en query.
            navigator.sendBeacon('/track?_token=' + encodeURIComponent(csrfToken()), blob);
            return;
        }

        fetch('/track', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body,
            keepalive: true,
        }).catch(() => { /* silent */ });
    } catch { /* silent */ }
}

export function trackClick(name, metadata = {}) {
    track('button_click', name, metadata);
}

export function trackView(name, metadata = {}) {
    track('view', name, metadata);
}
