export default function GuestLayout({ title, children }) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <div className="w-full max-w-sm">
                <h1 className="mb-6 text-center text-xl font-semibold text-gray-900">
                    UATF · Designaciones
                </h1>
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    {title && <h2 className="mb-4 text-lg font-medium text-gray-900">{title}</h2>}
                    {children}
                </div>
            </div>
        </div>
    );
}
