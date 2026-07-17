import { useClipboard } from './ClipboardContext'

export default function ClipboardBanner() {
    const { clipboard, clear } = useClipboard()

    if (!clipboard || !clipboard.filas || clipboard.filas.length === 0) return null

    const count = clipboard.filas.length

    return (
        <div className="fixed bottom-0 inset-x-0 z-50 flex items-center justify-center p-4 pointer-events-none">
            <div className="pointer-events-auto flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-lg">
                <span className="text-sm text-gray-700">
                    <span className="font-semibold">{count}</span> designacion{count !== 1 ? 'es' : ''} copiada{count !== 1 ? 's' : ''}
                </span>

                <span className="text-xs text-gray-400">
                    ({clipboard.origen?.gestion_nombre || 'gestión'} / {clipboard.origen?.periodo_nombre || 'periodo'})
                </span>

                <button
                    onClick={clear}
                    className="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50"
                >
                    Limpiar
                </button>
            </div>
        </div>
    )
}
