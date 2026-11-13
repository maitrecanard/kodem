/**
 * Champ de formulaire public (libellé, contrôle, erreur) et style commun des contrôles `.input`.
 * Partagé par la page de contact et la page d'annonce des publicités par IA.
 */
export default function FormField({ label, error, children }) {
    return (
        <label className="block">
            <span className="block text-sm font-medium text-encre mb-1">{label}</span>
            {children}
            {error && <span className="block mt-1 text-sm text-rose-600">{error}</span>}
        </label>
    );
}

export function FormInputStyle() {
    return (
        <style>{`.input{width:100%;border:1px solid #C7D2FB;border-radius:.5rem;padding:.6rem .75rem;font-size:.95rem}.input:focus{outline:none;border-color:#2348E0;box-shadow:0 0 0 3px rgba(35,72,224,.15)}`}</style>
    );
}
