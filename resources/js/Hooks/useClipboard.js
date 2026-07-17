const STORAGE_KEY = 'designaciones_clipboard'
const EXPIRY_MS = 30 * 60 * 1000

export function clipboardRead() {
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

export function clipboardCopy(filas, origen) {
    const data = { filas, origen, timestamp: Date.now() }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
    window.dispatchEvent(new Event('clipboard-changed'))
    return data
}

export function clipboardClear() {
    localStorage.removeItem(STORAGE_KEY)
    window.dispatchEvent(new Event('clipboard-changed'))
}
