import { Icono } from './Icono';

export default function WarningBanner({ mensaje }) {
    if (!mensaje) {
        return null;
    }

    return (
        <div className="flex items-start gap-2.5 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-700">
            <Icono tipo="alerta" className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
            <span>{mensaje}</span>
        </div>
    );
}
