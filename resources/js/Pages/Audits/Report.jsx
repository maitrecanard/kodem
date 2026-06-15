export default function Report({ domain, status, amount, currency }) {
    const price =
        amount != null
            ? `${(amount / 100).toFixed(2)} ${String(currency || '').toUpperCase()}`
            : null;

    const body = {
        queued: "Votre rapport est en cours de préparation. Vous recevrez une notification dès qu'il est prêt.",
        in_progress: 'Votre rapport est en cours de préparation.',
        completed: null,
        refunded: "Cette commande a été remboursée. Le rapport n'est plus disponible.",
    }[status] ?? 'Votre rapport est en cours de préparation.';

    return (
        <div className="text-center py-16">
            <h1>Rapport d&apos;audit — {domain}</h1>
            {status === 'completed' ? (
                <>
                    <p>Votre audit premium pour <strong>{domain}</strong> est disponible.</p>
                    {price && <p>Montant réglé : {price}</p>}
                </>
            ) : (
                <p>{body}</p>
            )}
        </div>
    );
}
