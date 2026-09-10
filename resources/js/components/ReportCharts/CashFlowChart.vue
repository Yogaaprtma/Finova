<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

const props = defineProps<{
    chartData: {
        labels: string[];
        inflow: number[];
        outflow: number[];
        net_flow: number[];
        cumulative_flow: number[];
    };
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const formatCurrency = (val: number): string => {
    return (
        (val < 0 ? '-Rp ' : 'Rp ') +
        new Intl.NumberFormat('id-ID').format(Math.abs(val))
    );
};

const renderChart = () => {
    if (!canvasRef.value) {
        return;
    }

    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = canvasRef.value.getContext('2d');

    if (!ctx) {
        return;
    }

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    label: 'Net Cash Flow',
                    data: props.chartData.net_flow,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'Cumulative Cash Flow',
                    data: props.chartData.cumulative_flow,
                    borderColor: '#8B5CF6',
                    borderDash: [5, 5],
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        padding: 16,
                        font: { size: 12 },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            const val = Number(context.parsed.y ?? 0);

                            return ` ${context.dataset.label}: ${formatCurrency(val)}`;
                        },
                    },
                },
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    grid: { color: 'rgba(148, 163, 184, 0.1)' },
                    ticks: {
                        callback: function (val) {
                            const num = Number(val);

                            if (Math.abs(num) >= 1000000000) {
                                return `${(num / 1000000000).toFixed(1)}B`;
                            }

                            if (Math.abs(num) >= 1000000) {
                                return `${(num / 1000000).toFixed(1)}M`;
                            }

                            if (Math.abs(num) >= 1000) {
                                return `${(num / 1000).toFixed(0)}k`;
                            }

                            return num;
                        },
                    },
                },
            },
        },
    });
};

watch(
    () => props.chartData,
    () => renderChart(),
    { deep: true },
);
onMounted(() => renderChart());
onBeforeUnmount(() => chartInstance?.destroy());
</script>

<template>
    <div class="relative h-72 w-full">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
