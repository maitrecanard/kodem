export default function SectionLabel({ children, number, className = '' }) {
    return (
        <p className={`kodem-eyebrow flex items-center gap-2 ${className}`}>
            {number && <span className="text-cobalt-400">{number} —</span>}
            <span>// {children}</span>
        </p>
    );
}
