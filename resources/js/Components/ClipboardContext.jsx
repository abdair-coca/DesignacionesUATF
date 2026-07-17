import { createContext, useCallback, useContext, useEffect, useState } from 'react'

const STORAGE_KEY = 'designaciones_clipboard'
const EXPIRY_MS = 30 * 60 * 1000 // 30 minutos

const ClipboardContext = createContext(null)

function readFromStorage() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY)
        if (!raw) return null
        const data = JSON.parse(raw)
        if (Date.now() - data.timestamp > EXPIRY_MS) {
            localStorage.removeItem(STORAGE_KEY)
            return null
        }
        return data
    } catch {
        localStorage.removeItem(STORAGE_KEY)
        return null
    }
}

function writeToStorage(data) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
}

function clearStorage() {
    localStorage.removeItem(STORAGE_KEY)
}

export function ClipboardProvider({ children }) {
    const [clipboard, setClipboard] = useState(() => readFromStorage())
    const [undoIds, setUndoIds] = useState(null)

    // Sync entre pestañas
    useEffect(() => {
        const handler = (e) => {
            if (e.key === STORAGE_KEY) {
                setClipboard(readFromStorage())
            }
        }
        window.addEventListener('storage', handler)
        return () => window.removeEventListener('storage', handler)
    }, [])

    const copy = useCallback((filas, origen) => {
        const data = {
            filas,
            origen,
            timestamp: Date.now(),
        }
        writeToStorage(data)
        setClipboard(data)
    }, [])

    const clear = useCallback(() => {
        clearStorage()
        setClipboard(null)
    }, [])

    const setUndoData = useCallback((ids) => {
        setUndoIds(ids)
    }, [])

    const undo = useCallback(async () => {
        if (!undoIds || undoIds.length === 0) return null
        try {
            const response = await fetch(route('designaciones.deshacer'), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''
                    ),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids: undoIds }),
            })
            const result = await response.json()
            setUndoIds(null)
            return result
        } catch {
            return null
        }
    }, [undoIds])

    return (
        <ClipboardContext.Provider
            value={{ clipboard, copy, clear, undo, setUndoData, undoIds }}
        >
            {children}
        </ClipboardContext.Provider>
    )
}

export function useClipboard() {
    const ctx = useContext(ClipboardContext)
    if (!ctx) throw new Error('useClipboard must be used within ClipboardProvider')
    return ctx
}
