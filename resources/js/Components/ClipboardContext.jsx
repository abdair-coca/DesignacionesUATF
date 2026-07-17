import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import { clipboardRead } from '../Hooks/useClipboard'

const ClipboardContext = createContext(null)

export function ClipboardProvider({ children }) {
    const [clipboard, setClipboard] = useState(() => clipboardRead())
    const [tick, setTick] = useState(0)

    useEffect(() => {
        const handler = () => setClipboard(clipboardRead())
        window.addEventListener('clipboard-changed', handler)
        window.addEventListener('storage', handler)
        return () => {
            window.removeEventListener('clipboard-changed', handler)
            window.removeEventListener('storage', handler)
        }
    }, [])

    const clear = useCallback(() => {
        localStorage.removeItem('designaciones_clipboard')
        setClipboard(null)
        window.dispatchEvent(new Event('clipboard-changed'))
    }, [])

    return (
        <ClipboardContext.Provider value={{ clipboard, clear }}>
            {children}
        </ClipboardContext.Provider>
    )
}

export function useClipboardCtx() {
    return useContext(ClipboardContext)
}
