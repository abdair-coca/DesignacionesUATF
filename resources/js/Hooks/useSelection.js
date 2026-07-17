import { useCallback, useMemo, useRef, useState } from 'react'

/**
 * Multi-select con Ctrl+Click para tablas.
 */
export function useSelection() {
    const [selected, setSelected] = useState(new Set())
    const selectedRef = useRef(selected)
    selectedRef.current = selected

    const toggle = useCallback((id, ctrlKey) => {
        setSelected((prev) => {
            const next = new Set(ctrlKey ? prev : [])
            if (next.has(id)) {
                next.delete(id)
            } else {
                next.add(id)
            }
            return next
        })
    }, [])

    const selectAll = useCallback((ids) => {
        setSelected(new Set(ids))
    }, [])

    const clearAll = useCallback(() => {
        setSelected(new Set())
    }, [])

    const isSelected = useCallback((id) => selectedRef.current.has(id), [])

    return useMemo(() => ({
        selected,
        toggle,
        selectAll,
        clearAll,
        isSelected,
        count: selected.size,
        selectedIds: [...selected],
    }), [selected, toggle, selectAll, clearAll, isSelected])
}
