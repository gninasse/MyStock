/**
 * statistiques.js
 * Graphiques de la page Statistiques (ApexCharts), à partir des données JSON de la page.
 */
(function () {
    const donnees = JSON.parse(document.getElementById('donnees-stats').textContent);
    const nombre = new Intl.NumberFormat('fr-FR');
    const sombre = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const commun = {
        chart: { fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false }, foreColor: sombre ? '#adb5bd' : '#5f6b76', animations: { speed: 400 } },
        grid: { borderColor: sombre ? '#343a40' : '#e9ecef', strokeDashArray: 4 },
        dataLabels: { enabled: false },
        tooltip: { theme: sombre ? 'dark' : 'light', y: { formatter: (v) => nombre.format(v) } },
        legend: { position: 'top', horizontalAlign: 'left', fontSize: '13px', markers: { size: 6 } },
        noData: { text: 'Aucune donnée sur la période' },
    };
    const COULEURS = { entrees: '#2f9e44', consommation: '#1c7ed6', transferts: '#f08c00' };

    function graphe(id, options) {
        const el = document.getElementById(id);
        if (el) new ApexCharts(el, { ...commun, ...options, chart: { ...commun.chart, ...options.chart } }).render();
    }

    const evo = donnees.evolution;
    graphe('graphe-evolution', {
        chart: { type: 'area', height: 320, stacked: false },
        series: [
            { name: 'Entrées', data: evo.series.entrees },
            { name: 'Consommation', data: evo.series.consommation },
            { name: 'Transferts expédiés', data: evo.series.transferts },
        ],
        colors: [COULEURS.entrees, COULEURS.consommation, COULEURS.transferts],
        stroke: { curve: 'smooth', width: 2.5 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 95] } },
        xaxis: { categories: evo.libelles, tickAmount: Math.min(evo.libelles.length, 12), labels: { rotate: 0, hideOverlappingLabels: true } },
        yaxis: { labels: { formatter: (v) => nombre.format(Math.round(v)) } },
        markers: { size: evo.libelles.length > 20 ? 0 : 3, strokeWidth: 0, hover: { size: 5 } },
    });

    const services = donnees.services;
    graphe('graphe-services', {
        chart: { type: 'donut', height: 320 },
        series: services.map((s) => s.quantite),
        labels: services.map((s) => s.libelle),
        colors: ['#1c7ed6', '#4dabf7', '#15aabf', '#20c997', '#94d82d', '#fab005', '#adb5bd'],
        legend: { position: 'bottom', fontSize: '12px' },
        stroke: { width: 2, colors: [sombre ? '#212529' : '#fff'] },
        plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Total', formatter: (w) => nombre.format(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) }, value: { formatter: (v) => nombre.format(v) } } } } },
    });

    const articles = donnees.articles;
    graphe('graphe-articles', {
        chart: { type: 'bar', height: 340 },
        series: [{ name: 'Quantité consommée', data: articles.map((a) => a.quantite) }],
        colors: [COULEURS.consommation],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '62%', dataLabels: { position: 'top' } } },
        dataLabels: { enabled: true, offsetX: 26, style: { fontSize: '11px', colors: [sombre ? '#dee2e6' : '#343a40'], fontWeight: 600 }, formatter: (v) => nombre.format(v) },
        xaxis: { categories: articles.map((a) => a.libelle), labels: { formatter: (v) => nombre.format(v) } },
        tooltip: { ...commun.tooltip, y: { formatter: (v, { dataPointIndex }) => `${nombre.format(v)} ${articles[dataPointIndex]?.unite ?? ''}` } },
        grid: { ...commun.grid, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    });

    const sante = donnees.sante;
    graphe('graphe-sante', {
        chart: { type: 'bar', height: 340, stacked: true, stackType: '100%' },
        series: [
            { name: 'Rupture', data: sante.rupture },
            { name: 'Sous le seuil', data: sante.sous },
            { name: 'Au seuil', data: sante.seuil },
            { name: 'Normal', data: sante.normal },
        ],
        colors: ['#e03131', '#f76707', '#fcc419', '#40c057'],
        plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 3 } },
        dataLabels: { enabled: true, formatter: (v, { seriesIndex, dataPointIndex, w }) => w.config.series[seriesIndex].data[dataPointIndex] || '', style: { fontSize: '11px' } },
        xaxis: { categories: sante.magasins, labels: { formatter: (v) => `${Math.round(v)} %` } },
        tooltip: { ...commun.tooltip, y: { formatter: (v, { seriesIndex, dataPointIndex, w }) => `${w.config.series[seriesIndex].data[dataPointIndex]} article(s)` } },
    });
})();
