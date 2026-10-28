<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

Chart.register(...registerables);

const props = defineProps<{
    allocation: {
        labels: string[];
        values: number[];
        colors: string[];
        items: Array<{
            label: string;
            value: number;
            color: string;
            percentage: number;
        }>;
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
            labels: props.allocation.labels,
            datasets: [
                {
                    data: props.allocation.values,
                    backgroundColor: props.allocation.colors,
                    borderColor: 'transparent',
                    borderWidth: 2,
                    hoverOffset: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    display: false, // Custom legend below
                },
                tooltip: {
                    enabled: props.allocation.total > 0,
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            const val = Number(context.parsed);
                            const total = props.allocation.total || 1;
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
    () => props.allocation,
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
    <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
        <!-- Donut Canvas with Center Stats -->
        <div
            class="relative flex h-52 w-52 shrink-0 items-center justify-center"
        >
            <canvas ref="canvasRef"></canvas>
            <div
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"
            >
                <span class="text-xs font-medium text-muted-foreground"
                    >Total Assets</span
                >
                <span class="mt-0.5 text-base font-bold tracking-tight">
                    <MoneyDisplay :amount="allocation.total" />
                </span>
            </div>
        </div>

        <!-- Legend Items -->
        <div class="flex w-full flex-1 flex-col justify-center gap-2.5">
            <div
                v-if="allocation.items.length === 0"
                class="py-4 text-center text-sm text-muted-foreground sm:text-left"
            >
                No active assets or cash balances found. Add an account or asset
                to see allocation.
            </div>
            <div
                v-for="item in allocation.items"
                :key="item.label"
                class="flex items-center justify-between text-sm"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <span
                        class="h-3 w-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: item.color }"
                    ></span>
                    <span class="truncate font-medium text-foreground">{{
                        item.label
                    }}</span>
                </div>
                <div class="flex shrink-0 items-center gap-3 text-right">
                    <span class="font-semibold text-foreground">
                        <MoneyDisplay :amount="item.value" />
                    </span>
                    <span
                        class="w-12 text-xs font-medium text-muted-foreground"
                    >
                        {{ item.percentage }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
