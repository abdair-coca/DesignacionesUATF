import { Link, router, usePage } from '@inertiajs/react';

const navItems = [
    { label: 'Designación de Docentes', href: () => route('designaciones.index'), match: '/designaciones' },
];

export default function AppLayout({ title, children }) {
    const { flash, auth } = usePage().props;
    const currentUrl = usePage().url;
    const status = flash?.status;

    function logout(e) {
        e.preventDefault();
        router.post(route('logout'));
    }

    return (
        <div className="flex min-h-screen bg-gray-50 text-gray-900">
            <nav className="flex w-64 shrink-0 flex-col bg-gray-900 text-white p-4">
                <h1 className="text-lg font-semibold mb-6">UATF · Designaciones</h1>
                <ul className="flex-1 space-y-1">
                    {navItems.map((item) => {
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
            </nav>

            <main className="flex-1 p-8">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">{title}</h2>

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

                {children}
            </main>
        </div>
    );
}
