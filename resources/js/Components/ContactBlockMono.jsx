import BrandWordmark from '@/Components/BrandWordmark';

export default function ContactBlockMono({
    web = 'kodem.fr',
    email = 'contact@kodem.fr',
    phone = '+33 (0)0 00 00 00 00',
    address = 'Adresse à compléter',
    className = '',
}) {
    const telHref = 'tel:' + phone.replace(/[^+\d]/g, '');
    return (
        <div className={`bg-encre text-brume rounded-kodem p-8 ${className}`}>
            <BrandWordmark className="text-xl text-white" bracketClassName="text-cobalt-400" />
            <div className="mt-2 h-px w-12 bg-cobalt-500" />
            <ul className="mt-6 space-y-3">
                <li className="flex items-baseline gap-3 font-mono text-sm">
                    <span className="text-cobalt-400">→</span>
                    <a href={`https://${web}`} className="hover:text-white">{web}</a>
                </li>
                <li className="flex items-baseline gap-3 font-mono text-sm">
                    <span className="text-cobalt-400">→</span>
                    <a href={`mailto:${email}`} className="hover:text-white">{email}</a>
                </li>
                <li className="flex items-baseline gap-3 font-mono text-sm">
                    <span className="text-cobalt-400">→</span>
                    <a href={telHref} className="hover:text-white">{phone}</a>
                </li>
                <li className="flex items-baseline gap-3 font-mono text-sm">
                    <span className="text-cobalt-400">→</span>
                    <span>{address}</span>
                </li>
            </ul>
        </div>
    );
}
