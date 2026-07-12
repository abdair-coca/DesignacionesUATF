import { Link, router, usePage } from '@inertiajs/react';

const navGroups = [
    {
        items: [{ label: 'Dashboard', href: () => route('dashboard'), match: '/dashboard' }],
    },
    {
        items: [{ label: 'Designación de Docentes', href: () => route('designaciones.index'), match: '/designaciones' }],
    },
    {
        titulo: 'Catálogos',
        items: [
            { label: 'Carreras', href: () => route('carreras.index'), match: '/carreras' },
            { label: 'Materias', href: () => route('materias.index'), match: '/materias' },
            { label: 'Grupos', href: () => route('grupos.index'), match: '/grupos' },
            { label: 'Docentes', href: () => route('docentes.index'), match: '/docentes' },
            { label: 'Gestiones', href: () => route('gestiones.index'), match: '/gestiones' },
            { label: 'Periodos', href: () => route('periodos.index'), match: '/periodos' },
        ],
    },
];

export default function AppLayout({ title, children }) {
    const { flash, auth } = usePage().props;
    const currentUrl = usePage().url;
    const status = flash?.status;
    const error = flash?.error;

    function logout(e) {
        e.preventDefault();
        router.post(route('logout'));
    }

    return (
        <div className="flex min-h-screen bg-gray-50 text-gray-900">
            <nav className="flex w-64 shrink-0 flex-col bg-gray-900 text-white p-4">
                <h1 className="text-lg font-semibold mb-6">UATF · Designaciones</h1>
                <div className="flex-1 space-y-6">
                    {navGroups.map((grupo, indice) => (
                        <div key={grupo.titulo ?? indice}>
                            {grupo.titulo && (
                                <p className="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                    {grupo.titulo}
                                </p>
                            )}
                            <ul className="space-y-1">
                                {grupo.items.map((item) => {
                                    const active = currentUrl.startsWith(item.match);
                                    return (
                                        <li key={item.label}>
                                            <Link
                                                href={item.href()}
                                                className={`block rounded px-3 py-2 text-sm transition-colors ${
                                                    active
                                                        ? 'bg-gray-800 font-medium text-white'
                                                        : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                                                }`}
                                            >
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </div>
            </nav>

            <main className="flex-1 p-8">
                <div className={`flex items-center justify-between ${title ? 'mb-6' : 'mb-4'}`}>
                    {title ? <h2 className="text-2xl font-semibold">{title}</h2> : <div />}

                    {auth?.user && (
                        <div className="flex items-center gap-3 text-sm">
                            <span className="text-gray-600">{auth.user.name}</span>
                            <button
                                onClick={logout}
                                className="rounded-md border border-gray-300 px-3 py-1.5 text-gray-700 hover:bg-gray-100"
                            >
                                Cerrar sesión
                            </button>
                        </div>
                    )}
                </div>

                {status && (
                    <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {status}
                    </div>
                )}

                {error && (
                    <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {error}
                    </div>
                )}

                {children}
            </main>
        </div>
    );
}
