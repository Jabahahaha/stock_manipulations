import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Lazy-load ApexCharts only when needed
window.loadApexCharts = () => import('apexcharts').then(m => {
    window.ApexCharts = m.default;
    return m.default;
});

Alpine.start();
