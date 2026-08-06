/**
 * Construit le titre d'onglet à partir du titre déclaré par la page.
 *
 * Les pages publiques portent déjà la marque dans leur meta title (« … | Kodem »,
 * défini dans PublicController) ; y ajouter le suffixe aveuglément donnait
 * « … | Kodem - Kodem ». Les pages d'administration et d'authentification, elles,
 * déclarent un titre court (« Log in », « Statistiques ») et ont besoin du suffixe.
 *
 * Partagé par app.jsx (client) et ssr.jsx (serveur) : les deux DOIVENT produire le
 * même titre, sinon le titre rendu côté serveur diffère de celui posé au montage.
 */
export default function pageTitle(title, appName) {
    if (!title) {
        return appName;
    }

    return title.includes(appName) ? title : `${title} - ${appName}`;
}
