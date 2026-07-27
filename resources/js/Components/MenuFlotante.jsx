import { useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

export default function MenuFlotante({ anchorEl, onClose, width = 224, align = 'right', children }) {
    const [coords, setCoords] = useState(null);
    const menuRef = useRef(null);

    useLayoutEffect(() => {
        if (!anchorEl) return;

        function calcular(menuH) {
            const rect = anchorEl.getBoundingClientRect();
            const vh = window.innerHeight;
            const spaceBelow = vh - rect.bottom;
            const spaceAbove = rect.top;
            const left = align === 'left' ? rect.left : rect.right - width;
            const h = menuH || 320;
            const flip = spaceBelow < h && spaceAbove > spaceBelow;
            return {
                top: flip ? Math.max(rect.top - h - 6, 4) : rect.bottom + 6,
                left,
            };
        }

        setCoords(calcular());

        const raf = requestAnimationFrame(() => {
            if (menuRef.current) {
                setCoords(calcular(menuRef.current.offsetHeight));
            }
        });

        function actualizarScroll() {
            if (menuRef.current) {
                setCoords(calcular(menuRef.current.offsetHeight));
            }
        }

        window.addEventListener('scroll', actualizarScroll, true);
        window.addEventListener('resize', actualizarScroll);
        return () => {
            cancelAnimationFrame(raf);
            window.removeEventListener('scroll', actualizarScroll, true);
            window.removeEventListener('resize', actualizarScroll);
        };
    }, [anchorEl, width, align]);

    if (!anchorEl || !coords) return null;

    return createPortal(
        <>
            <div className="fixed inset-0 z-40" onClick={onClose} />
            <div
                ref={menuRef}
                className="menu-pop fixed z-50 rounded-lg border border-gray-200/80 bg-white py-1 shadow-lg shadow-gray-200/60"
                style={{ top: coords.top, left: coords.left, width }}
            >
                {children}
            </div>
        </>,
        document.body
    );
}
