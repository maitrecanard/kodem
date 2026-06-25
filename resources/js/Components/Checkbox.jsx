export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-cobalt-300 text-cobalt-600 shadow-sm focus:ring-cobalt-500 ' +
                className
            }
        />
    );
}
