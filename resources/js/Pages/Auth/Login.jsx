import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const inputClass =
    'w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm transition-colors hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e) {
        e.preventDefault();
        post(route('login'));
    }

    return (
        <AppLayout title="Iniciar sesión">
            <div className="flex min-h-[50vh] items-center justify-center py-6 px-4">
                <form
                    onSubmit={submit}
                    className="w-full max-w-sm space-y-5 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm"
                >
                    <div className="text-center">
                        <h2 className="text-xl font-bold tracking-tight text-gray-900">Iniciar sesión</h2>
                        <p className="mt-1 text-xs text-gray-500">Ingresa tus credenciales para acceder al sistema.</p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1.5">Correo electrónico</label>
                            <input
                                type="email"
                                className={inputClass}
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="nombre@correo.com"
                                autoFocus
                            />
                            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1.5">Contraseña</label>
                            <input
                                type="password"
                                className={inputClass}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="••••••••"
                            />
                            {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                        </div>

                        <div className="flex items-center">
                            <label className="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-blue-900 focus:ring-blue-500/20"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                />
                                Recordarme
                            </label>
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Ingresar
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
