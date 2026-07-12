import { Link } from '@inertiajs/react';

export default function Pagination({ paginador, etiqueta }) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3">
            <p className="text-[13px] text-gray-400 tabular-nums">
                Mostrando <span className="font-medium text-gray-600">{paginador.from ?? 0}</span> a{' '}
                <span className="font-medium text-gray-600">{paginador.to ?? 0}</span> de{' '}
                <span className="font-medium text-gray-600">{paginador.total}</span> {etiqueta}
            </p>
            {paginador.links.length > 3 && (
                <div className="flex gap-1">
                    {paginador.links.map((link, i) => {
                        const etiquetaLink = link.label.includes('Previous')
                            ? '‹'
                            : link.label.includes('Next')
                              ? '›'
                              : link.label;
                        return (
                            <Link
                                key={i}
                                href={link.url || '#'}
                                dangerouslySetInnerHTML={{ __html: etiquetaLink }}
                                className={`flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition-colors tabular-nums ${
                                    link.active
                                        ? 'bg-blue-900 font-medium text-white shadow-sm'
                                        : link.url
                                          ? 'border border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                                          : 'cursor-not-allowed text-gray-300'
                                }`}
                            />
                        );
                    })}
                </div>
            )}
        </div>
    );
}
