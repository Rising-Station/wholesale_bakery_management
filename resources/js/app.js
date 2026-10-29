import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import DialogService from 'primevue/dialogservice';
import Tooltip from 'primevue/tooltip';
import Select from 'primevue/select';
import { route } from './routes';
import { formatDate, formatDateTime } from './Utils/date';

// Enable search filtering by default for all PrimeVue Select dropdowns
const selectProps = Select?.props || Select?.extends?.props;
if (selectProps) {
    selectProps.filter = { type: Boolean, default: true };
}

window.route = route;

const appName = import.meta.env.VITE_APP_NAME || 'Mominul Bekari ERP';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });

        vueApp.config.globalProperties.route = route;
        vueApp.config.globalProperties.$formatDate = formatDate;
        vueApp.config.globalProperties.$formatDateTime = formatDateTime;

        vueApp.use(plugin);
        vueApp.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '.app-dark',
                    cssLayer: false,
                },
            },
            ripple: true,
        });
        vueApp.use(ToastService);
        vueApp.use(ConfirmationService);
        vueApp.use(DialogService);
        vueApp.directive('tooltip', Tooltip);

        vueApp.mount(el);
    },
    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
});
