import { Icono } from './Icono';

export default function Select({ value, onChange, children, className = '' }) {
    return (
        <div className={`relative ${className}`}>
            <select
                value={value}
                onChange={onChange}
                className="w-full cursor-pointer appearance-none rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 shadow-sm transition-colors hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            >
                {children}
            </select>
            <span className="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400">
                <Icono tipo="chevronAbajo" className="h-3.5 w-3.5" />
            </span>
        </div>
    );
}
