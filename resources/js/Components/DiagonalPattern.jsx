export default function DiagonalPattern({ children, className = '', as: Tag = 'div', ...props }) {
    return <Tag className={`kodem-diagonal ${className}`} {...props}>{children}</Tag>;
}
