import { useCallback, useState } from 'react'

/**
 * Multi-select con Ctrl+Click para tablas.
 *
 * @param {Object} options
 * @param {Function} options.onCopy - callback al presionar Ctrl+C (recibe selectedIds)
 * @returns {{ selected, toggle, selectAll, clearAll, isSelected, count }}
 */
export function useSelection({ onCopy } = {}) {
    const [selected, setSelected] = useState(new Set())

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

    const isSelected = useCallback((id) => selected.has(id), [selected])

    return {
        selected,
        toggle,
        selectAll,
        clearAll,
        isSelected,
        count: selected.size,
        selectedIds: [...selected],
    }
}
