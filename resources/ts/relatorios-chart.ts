declare const Chart: any;

type FilterKey = 'monthSales' | 'monthPurchases' | 'monthRent' | 'monthObra' | 'monthTaxas';

interface MonthlySeries {
    label: string;
    monthSales: number;
    monthPurchases: number;
    monthRent: number;
    monthObra: number;
    monthTaxas: number;
    delta: number;
    cumulative: number;
}

type FilterState = Record<FilterKey, boolean>;

const FILTER_KEYS: FilterKey[] = ['monthSales', 'monthPurchases', 'monthRent', 'monthObra', 'monthTaxas'];
const POSITIVE_KEYS: FilterKey[] = ['monthSales', 'monthRent'];
const NEGATIVE_KEYS: FilterKey[] = ['monthPurchases', 'monthObra', 'monthTaxas'];
const currencyFormatter = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const createDefaultFilterState = (): FilterState => ({
    monthSales: true,
    monthPurchases: true,
    monthRent: true,
    monthObra: true,
    monthTaxas: true,
});

function formatCurrency(value: number): string {
    const sign = value < 0 ? '-' : '';
    const absolute = Math.abs(value);
    return `${sign}R$ ${currencyFormatter.format(absolute)}`;
}

const isFilterKey = (value: string | undefined): value is FilterKey => !!value && FILTER_KEYS.includes(value as FilterKey);

const normalizeSeries = (input: any[]): MonthlySeries[] => input.map((item) => ({
    label: String(item.label ?? ''),
    monthSales: Number(item.monthSales ?? 0),
    monthPurchases: Number(item.monthPurchases ?? 0),
    monthRent: Number(item.monthRent ?? 0),
    monthObra: Number(item.monthObra ?? 0),
    monthTaxas: Number(item.monthTaxas ?? 0),
    delta: Number(item.delta ?? 0),
    cumulative: Number(item.cumulative ?? 0),
}));

const getFilterState = (controls: HTMLInputElement[], fallback: FilterState): FilterState => {
    const nextState: FilterState = { ...fallback };
    controls.forEach((control) => {
        const key = control.dataset.key;
        if (!isFilterKey(key)) return;
        nextState[key] = control.checked;
    });
    return nextState;
};

const recalculateSeries = (series: MonthlySeries[], state: FilterState): MonthlySeries[] => {
    let runningTotal = 0;
    return series.map((entry) => {
        const result: MonthlySeries = {
            label: entry.label,
            monthSales: state.monthSales ? entry.monthSales : 0,
            monthPurchases: state.monthPurchases ? entry.monthPurchases : 0,
            monthRent: state.monthRent ? entry.monthRent : 0,
            monthObra: state.monthObra ? entry.monthObra : 0,
            monthTaxas: state.monthTaxas ? entry.monthTaxas : 0,
            delta: 0,
            cumulative: 0,
        };

        const positiveTotal = POSITIVE_KEYS.reduce((acc, key) => acc + (state[key] ? entry[key] : 0), 0);
        const negativeTotal = NEGATIVE_KEYS.reduce((acc, key) => acc + (state[key] ? entry[key] : 0), 0);
        result.delta = positiveTotal - negativeTotal;
        runningTotal += result.delta;
        result.cumulative = runningTotal;
        return result;
    });
};

const updateChart = (chart: any, recalculated: MonthlySeries[]) => {
    if (!chart || !chart.data || !chart.data.datasets || !chart.data.datasets[0]) return;
    chart.data.datasets[0].data = recalculated.map((item) => item.cumulative);
    chart.update();
};

const updateTable = (rows: HTMLTableRowElement[], recalculated: MonthlySeries[]): void => {
    if (!rows.length) return;
    rows.forEach((row, index) => {
        const current = recalculated[index];
        if (!current) return;

        const setCellValue = (field: string, display: string) => {
            const cell = row.querySelector<HTMLElement>(`[data-field="${field}"]`);
            if (!cell) return;
            cell.textContent = display;
        };

        setCellValue('monthSales', formatCurrency(current.monthSales));
        setCellValue('monthPurchases', formatCurrency(current.monthPurchases));
        setCellValue('monthRent', formatCurrency(current.monthRent));
        setCellValue('monthObra', formatCurrency(current.monthObra));
        setCellValue('monthTaxas', formatCurrency(current.monthTaxas));
        setCellValue('delta', formatCurrency(current.delta));
        setCellValue('cumulative', formatCurrency(current.cumulative));
    });
};

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('patrimonioChart') as HTMLCanvasElement | null;
    if (!canvas) return;
    const raw = canvas.getAttribute('data-series');
    if (!raw) return;

    let parsed: unknown;
    try {
        parsed = JSON.parse(raw);
    } catch (_error) {
        return;
    }

    if (!Array.isArray(parsed) || !parsed.length) return;

    const baseSeries = normalizeSeries(parsed);
    if (!baseSeries.length) return;

    const filterControls = Array.from(document.querySelectorAll<HTMLInputElement>('.js-relatorio-filter'));
    const tableRows = Array.from(document.querySelectorAll<HTMLTableRowElement>('[data-series-row]'));

    const initialState = getFilterState(filterControls, createDefaultFilterState());
    let currentSeries = recalculateSeries(baseSeries, initialState);

    const ctx = canvas.getContext('2d');
    let chartInstance: any = null;
    if (ctx && typeof Chart !== 'undefined') {
        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: baseSeries.map((item) => item.label),
                datasets: [{
                    label: 'Patrimônio (acumulado)',
                    data: currentSeries.map((item) => item.cumulative),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.2,
                    fill: true,
                }]
            },
            options: {
                scales: {
                    y: {
                        ticks: {
                            callback: (value: number | string) => {
                                const numeric = typeof value === 'string' ? Number(value) : value;
                                return formatCurrency(Number.isFinite(numeric) ? Number(numeric) : 0);
                            }
                        }
                    }
                }
            }
        });
    }

    updateTable(tableRows, currentSeries);

    const applyFilters = () => {
        currentSeries = recalculateSeries(baseSeries, getFilterState(filterControls, createDefaultFilterState()));
        updateTable(tableRows, currentSeries);
        updateChart(chartInstance, currentSeries);
    };

    filterControls.forEach((control) => control.addEventListener('change', applyFilters));
});
