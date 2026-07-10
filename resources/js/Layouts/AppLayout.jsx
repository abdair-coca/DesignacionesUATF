import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ title, children }) {
    const { flash, url } = usePage().props;
    const status = flash?.status;
    const isDesignaciones = usePage().url.startsWith('/designaciones');

    return (
        <div className="flex min-h-screen bg-gray-50 text-gray-900">
            <nav className="w-64 shrink-0 bg-gray-900 text-white p-4">
                <h1 className="text-lg font-semibold mb-6">UATF · Designaciones</h1>
                <ul className="space-y-1">
                    <li>
                        <Link
                            href={route('designaciones.index')}
                            className={`block rounded px-3 py-2 text-sm transition-colors ${
                                isDesignaciones
                                    ? 'bg-gray-800 font-medium text-white'
                                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                            }`}
                        >
                            Designación de Docentes
                        </Link>
                    </li>
                </ul>
            </nav>

            <main className="flex-1 p-8">
                <h2 className="text-2xl font-semibold mb-6">{title}</h2>

                {status && (
                    <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {status}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
