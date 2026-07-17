import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import ClipboardBanner from './Components/ClipboardBanner';

import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Designación de Docentes UATF';

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />
                <ClipboardBanner />
            </>
        );
    },
});
