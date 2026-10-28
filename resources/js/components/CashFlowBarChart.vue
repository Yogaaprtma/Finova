<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

const props = defineProps<{
    chartData: {
        labels: string[];
        income: number[];
        expense: number[];
        net: number[];
    };
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const formatCurrency = (val: number): string => {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
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
        type: 'bar',
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    label: 'Income',
                    data: props.chartData.income,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    hoverBackgroundColor: 'rgba(16, 185, 129, 1)',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
                },
                {
                    label: 'Expense',
                    data: props.chartData.expense,
                    backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    hoverBackgroundColor: 'rgba(244, 63, 94, 1)',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
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
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 16,
                        color: 'currentColor',
                        font: {
                            family: 'inherit',
                            size: 12,
                            weight: 500,
                        },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 8,
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: function (context) {
                            const val = context.parsed.y ?? 0;

                            return `${context.dataset.label}: ${formatCurrency(val)}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: 'rgba(148, 163, 184, 0.9)',
                        font: {
                            size: 11,
                        },
                    },
                },
                y: {
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)',
                    },
                    ticks: {
                        color: 'rgba(148, 163, 184, 0.9)',
                        font: {
                            size: 11,
                        },
                        callback: function (val) {
                            const num = Number(val);

                            if (num >= 1000000000) {
                                return (num / 1000000000).toFixed(1) + 'M';
                            }

                            if (num >= 1000000) {
                                return (num / 1000000).toFixed(0) + 'jt';
                            }

                            if (num >= 1000) {
                                return (num / 1000).toFixed(0) + 'k';
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
    () => {
        renderChart();
    },
    { deep: true },
);

onMounted(() => {
    renderChart();
});

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>

<template>
    <div class="relative h-64 w-full">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
