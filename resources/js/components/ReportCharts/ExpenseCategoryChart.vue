<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

Chart.register(...registerables);

const props = defineProps<{
    chartData: {
        labels: string[];
        values: number[];
        colors: string[];
        total: number;
    };
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

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
        type: 'doughnut',
        data: {
            labels: props.chartData.labels,
            datasets: [
                {
                    data: props.chartData.values,
                    backgroundColor: props.chartData.colors,
                    borderColor: 'transparent',
                    borderWidth: 2,
                    hoverOffset: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            const val = Number(context.parsed);
                            const total = props.chartData.total || 1;
                            const pct = ((val / total) * 100).toFixed(1);
                            const formatted =
                                'Rp ' +
                                new Intl.NumberFormat('id-ID').format(val);

                            return ` ${context.label}: ${formatted} (${pct}%)`;
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
    <div class="relative flex h-64 w-full items-center justify-center">
        <div class="relative h-60 w-60">
            <canvas ref="canvasRef"></canvas>
            <div
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"
            >
                <span class="text-xs font-medium text-muted-foreground"
                    >Total Expense</span
                >
                <span
                    class="mt-0.5 text-base font-bold tracking-tight text-rose-600 dark:text-rose-400"
                >
                    <MoneyDisplay :amount="chartData.total" />
                </span>
            </div>
        </div>
    </div>
</template>
