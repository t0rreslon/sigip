/**
 * Gráficas de la página de inicio (Chart.js empaquetado con Vite, sin CDN).
 * Cada <canvas data-grafica="{...}"> trae su tipo, etiquetas y valores desde InicioController.
 */
import {
    Chart, BarController, LineController, BarElement, LineElement, PointElement,
    CategoryScale, LinearScale, Tooltip, Legend, Filler,
} from 'chart.js';

Chart.register(BarController, LineController, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const estilo = getComputedStyle(document.documentElement);
const color = (nombre, respaldo) => estilo.getPropertyValue(nombre).trim() || respaldo;
const SERIES = [color('--grafica-serie-1', '#2a78d6'), color('--grafica-serie-2', '#eb6834')];
const TEXTO = color('--grafica-texto', '#52514e');
const CUADRICULA = color('--grafica-cuadricula', '#ecebe8');

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = TEXTO;

const pesos = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });
const numero = new Intl.NumberFormat('es-CO');

export function crear(lienzo) {
    const g = JSON.parse(lienzo.dataset.grafica);
    const horizontal = g.tipo.startsWith('barh');
    const apilada = g.tipo === 'barh-apilada';
    const formato = (v) => (g.moneda ? pesos.format(v) : numero.format(v));

    const series = g.datos.series ?? [{ nombre: g.serie, valores: g.datos.valores }];
    const datasets = series.map((s, i) => ({
        label: s.nombre,
        data: s.valores,
        backgroundColor: g.tipo === 'line' ? SERIES[i] + '22' : SERIES[i],
        borderColor: SERIES[i],
        // Barras apiladas: borde del color de la tarjeta para separar los segmentos.
        borderWidth: g.tipo === 'line' ? 2 : (apilada ? { right: 2 } : 0),
        ...(apilada ? { borderColor: '#ffffff' } : {}),
        borderRadius: 4,
        borderSkipped: 'start',
        maxBarThickness: 28,
        pointRadius: 3,
        pointHoverRadius: 5,
        fill: g.tipo === 'line',
        // Rectas entre puntos: una curva suavizada inventaría valores entre días (incluso negativos).
        tension: 0,
    }));

    const ejeValor = {
        beginAtZero: true,
        grace: '10%',
        stacked: apilada,
        grid: { color: CUADRICULA },
        border: { display: false },
        ticks: { precision: 0, callback: (v) => (g.moneda ? pesos.format(v) : numero.format(v)) },
    };
    const ejeCategoria = { stacked: apilada, grid: { display: false }, border: { color: CUADRICULA } };

    new Chart(lienzo, {
        type: g.tipo === 'line' ? 'line' : 'bar',
        data: { labels: g.datos.etiquetas, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            interaction: { mode: 'index', intersect: false },
            scales: horizontal ? { x: ejeValor, y: ejeCategoria } : { x: ejeCategoria, y: ejeValor },
            plugins: {
                // Una sola serie: el título de la tarjeta la nombra, no hace falta leyenda.
                legend: { display: series.length > 1, position: 'top', align: 'start', labels: { boxWidth: 10, boxHeight: 10, useBorderRadius: true, borderRadius: 2 } },
                tooltip: {
                    callbacks: { label: (c) => ` ${c.dataset.label}: ${formato(c.parsed[horizontal ? 'x' : 'y'])}` },
                },
            },
        },
    });
}

document.querySelectorAll('canvas[data-grafica]:not([data-creada])').forEach((lienzo) => {
    lienzo.dataset.creada = '1';
    crear(lienzo);
});
