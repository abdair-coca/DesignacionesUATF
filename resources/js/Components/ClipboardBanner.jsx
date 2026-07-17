import { useEffect, useState } from 'react';
import { clipboardRead, clipboardClear } from '../Hooks/useClipboard';

export default function ClipboardBanner() {
    const [data, setData] = useState(() => clipboardRead());

    useEffect(() => {
        function refresh() {
            setData(clipboardRead());
        }

        window.addEventListener('clipboard-changed', refresh);
        window.addEventListener('storage', refresh);
        return () => {
            window.removeEventListener('clipboard-changed', refresh);
            window.removeEventListener('storage', refresh);
        };
    }, []);

    if (!data || !data.filas || data.filas.length === 0) return null;

    const count = data.filas.length;

    return (
        <div className="fixed bottom-0 inset-x-0 z-50 flex items-center justify-center p-4 pointer-events-none">
            <div className="pointer-events-auto flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-lg">
                <span className="text-sm text-gray-700">
                    <span className="font-semibold">{count}</span> designacion{count !== 1 ? 'es' : ''} copiada{count !== 1 ? 's' : ''}
                </span>

                <span className="text-xs text-gray-400">
                    ({data.origen?.gestion_nombre || 'gestión'} / {data.origen?.periodo_nombre || 'periodo'})
                </span>

                <button
                    onClick={() => {
                        clipboardClear();
                        setData(null);
                    }}
                    className="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50"
                >
                    Limpiar
                </button>
            </div>
        </div>
    )
}
