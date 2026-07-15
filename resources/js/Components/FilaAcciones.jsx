import { Link } from '@inertiajs/react'
import { Icono } from './Icono'
import ConfirmDeleteButton from './ConfirmDeleteButton'

export default function FilaAcciones({ editRoute, deleteRoute, children }) {
    return (
        <td className="px-4 py-3 text-right">
            <div className="flex items-center justify-end gap-1">
                {children}
                <Link
                    href={editRoute}
                    className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                >
                    <Icono tipo="lapiz" className="h-4 w-4" />
                </Link>
                <ConfirmDeleteButton deleteUrl={deleteRoute} />
            </div>
        </td>
    )
}
