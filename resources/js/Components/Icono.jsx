const trazos = {
    total: (
        <>
            <rect x="4" y="4" width="7" height="7" rx="1.5" />
            <rect x="13" y="4" width="7" height="7" rx="1.5" />
            <rect x="4" y="13" width="7" height="7" rx="1.5" />
            <rect x="13" y="13" width="7" height="7" rx="1.5" />
        </>
    ),
    check: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M9 12.5l2 2 4-5" />
        </>
    ),
    reloj: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7.5V12l3 2" />
        </>
    ),
    equis: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M9.5 9.5l5 5m0-5l-5 5" />
        </>
    ),
    buscar: (
        <>
            <circle cx="11" cy="11" r="6" />
            <path d="M20 20l-4-4" />
        </>
    ),
    cerrar: <path d="M6 6l12 12m0-12L6 18" />,
    embudo: <path d="M4 5h16l-6.2 7v5.5L10.2 19v-7z" />,
    ojo: (
        <>
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
            <circle cx="12" cy="12" r="3" />
        </>
    ),
    mas: <path d="M12 5v14m-7-7h14" />,
    lote: (
        <>
            <rect x="4" y="4" width="16" height="6" rx="1.5" />
            <rect x="4" y="14" width="16" height="6" rx="1.5" />
        </>
    ),
    copiar: (
        <>
            <rect x="8" y="8" width="12" height="12" rx="2" />
            <path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2" />
        </>
    ),
    documento: (
        <>
            <path d="M7 3.5h7L18.5 8v12a1.5 1.5 0 01-1.5 1.5H7A1.5 1.5 0 015.5 20V5A1.5 1.5 0 017 3.5z" />
            <path d="M14 3.5V8h4.5" />
        </>
    ),
    calendario: (
        <>
            <rect x="4" y="5" width="16" height="15" rx="2" />
            <path d="M4 9.5h16M8 3v4m8-4v4" />
        </>
    ),
    capas: (
        <>
            <path d="M12 3.5l8.5 4.5L12 12.5 3.5 8 12 3.5z" />
            <path d="M3.5 12L12 16.5 20.5 12M3.5 16L12 20.5 20.5 16" />
        </>
    ),
    libro: (
        <>
            <path d="M5 4.5A1.5 1.5 0 016.5 3H19v16.5H6.5A1.5 1.5 0 015 18V4.5z" />
            <path d="M5 18a1.5 1.5 0 011.5-1.5H19" />
        </>
    ),
    lapiz: (
        <>
            <path d="M4 20h4L18.5 9.5a2.121 2.121 0 00-3-3L5 17v3z" />
            <path d="M13 6l4 4" />
        </>
    ),
    papelera: (
        <>
            <path d="M5 7h14" />
            <path d="M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2" />
            <path d="M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
        </>
    ),
    alerta: (
        <>
            <path d="M12 3.5L21.5 20h-19L12 3.5z" />
            <path d="M12 9.5v4" />
            <path d="M12 17v.01" />
        </>
    ),
    flechaIzq: <path d="M15 6l-6 6 6 6" />,
    chevronDerecha: <path d="M9 6l6 6-6 6" />,
    chevronAbajo: <path d="M6 9l6 6 6-6" />,
};

export function Icono({ tipo, className = 'h-5 w-5' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            {trazos[tipo]}
        </svg>
    );
}

export function IconoPuntos({ className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" className={className}>
            <circle cx="12" cy="5.5" r="1.7" />
            <circle cx="12" cy="12" r="1.7" />
            <circle cx="12" cy="18.5" r="1.7" />
        </svg>
    );
}
