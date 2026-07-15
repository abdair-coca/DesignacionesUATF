import { useLayoutEffect, useState } from 'react';
import { createPortal } from 'react-dom';

export default function MenuFlotante({ anchorEl, onClose, width = 224, children }) {
    const [coords, setCoords] = useState(null);

    useLayoutEffect(() => {
        if (!anchorEl) return;

        function actualizarPosicion() {
            const rect = anchorEl.getBoundingClientRect();
            setCoords({ top: rect.bottom + 6, left: rect.right - width });
        }

        actualizarPosicion();
        window.addEventListener('scroll', actualizarPosicion, true);
        window.addEventListener('resize', actualizarPosicion);
        return () => {
            window.removeEventListener('scroll', actualizarPosicion, true);
            window.removeEventListener('resize', actualizarPosicion);
        };
    }, [anchorEl, width]);

    if (!anchorEl || !coords) return null;

    return createPortal(
        <>
            <div className="fixed inset-0 z-40" onClick={onClose} />
            <div
                className="menu-pop fixed z-50 rounded-lg border border-gray-200/80 bg-white py-1 shadow-lg shadow-gray-200/60"
                style={{ top: coords.top, left: coords.left, width }}
            >
                {children}
            </div>
        </>,
        document.body
    );
}
