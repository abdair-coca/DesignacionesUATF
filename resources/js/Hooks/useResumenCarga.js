import { router } from '@inertiajs/react'
import { useEffect } from 'react'

export function useResumenCarga(data) {
    useEffect(() => {
        const timeout = setTimeout(() => {
            router.reload({
                only: ['resumenCarga'],
                data: {
                    Id_docente: data.Id_docente || '',
                    Id_materia: data.Id_materia || '',
                    Id_grupo: data.Id_grupo || '',
                    Id_gestion: data.Id_gestion || '',
                    Id_periodo: data.Id_periodo || '',
                },
                preserveState: true,
                preserveScroll: true,
            })
        }, 300)
        return () => clearTimeout(timeout)
    }, [data.Id_docente, data.Id_materia, data.Id_grupo, data.Id_gestion, data.Id_periodo])
}
