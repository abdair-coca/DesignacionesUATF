import { router } from '@inertiajs/react'
import { useRef, useState } from 'react'

export function useDebouncedSearch(filtros, { delay = 350, only = [], preserveScroll = true } = {}) {
    const [valor, setValor] = useState(filtros.q ?? '')
    const timeoutRef = useRef(null)

    const buscar = (value, extras = {}) => {
        setValor(value)
        clearTimeout(timeoutRef.current)
        timeoutRef.current = setTimeout(() => {
            router.get(
                route(route().current()),
                { ...filtros, ...extras, q: value || undefined },
                { preserveState: true, preserveScroll, replace: true, only: only.length ? only : undefined }
            )
        }, delay)
    }

    return [valor, buscar]
}
