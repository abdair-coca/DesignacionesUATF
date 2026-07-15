import { Link } from '@inertiajs/react'

export default function FormActions({ onCancel, processing = false, label = 'Guardar' }) {
    return (
        <div className="flex justify-end gap-3 pt-4">
            <Link
                href={onCancel}
                className="border border-gray-200 bg-white px-4 py-2 rounded-lg text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition"
            >
                Cancelar
            </Link>
            <button
                type="submit"
                disabled={processing}
                className="bg-blue-900 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-800 active:scale-[0.98] transition disabled:opacity-50"
            >
                {processing ? 'Guardando...' : label}
            </button>
        </div>
    )
}
