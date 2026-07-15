export default function DataTable({ children }) {
    return (
        <div className="rounded-xl border border-gray-200/80 bg-white shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full">
                    {children}
                </table>
            </div>
        </div>
    )
}
