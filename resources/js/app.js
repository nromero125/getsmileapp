import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast, { useToast, POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

// Alpine.js setup
Alpine.plugin(persist);
window.Alpine = Alpine;
Alpine.start();

const appName = import.meta.env.VITE_APP_NAME || 'Dentaris';

createInertiaApp({
    title: (title) => title ? `${title} — ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, {
                position: POSITION.TOP_RIGHT,
                timeout: 3500,
                closeOnClick: true,
                pauseOnHover: true,
                draggable: true,
                hideProgressBar: false,
                toastClassName: 'dentaris-toast',
            })
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#00BFA6',
        showSpinner: false,
    },
});

// Dark mode
const theme = localStorage.getItem('theme');
if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
