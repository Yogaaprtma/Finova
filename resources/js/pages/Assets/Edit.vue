<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    asset: any;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Portfolio & Assets',
                href: '/assets',
            },
            {
                title: 'Edit Asset',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    name: props.asset.name,
    platform: props.asset.platform || '',
    ticker: props.asset.ticker || '',
    currency: props.asset.currency || 'IDR',
    manual_value: props.asset.manual_value,
    is_active: props.asset.is_active,
    notes: props.asset.notes || '',
});

const submit = () => {
    form.put(`/assets/${props.asset.id}`);
};
</script>

<template>
    <Head :title="`Edit ${asset.name} - Finova`" />

    <div class="mx-auto max-w-2xl p-4 md:p-6 lg:p-8">
        <div class="mb-6">
            <Link
                :href="`/assets/${asset.id}`"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Asset Details
            </Link>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                Edit Asset Details
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Update general information and notes for {{ asset.name }}.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1.5">
                    <Label for="name">Asset Name *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="rounded-xl"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label for="platform">Platform / Broker</Label>
                        <Input
                            id="platform"
                            v-model="form.platform"
                            type="text"
                            class="rounded-xl"
                        />
                        <InputError :message="form.errors.platform" />
                    </div>

                    <div v-if="asset.manual_value === null" class="space-y-1.5">
                        <Label for="ticker">Ticker Symbol</Label>
                        <Input
                            id="ticker"
                            v-model="form.ticker"
                            type="text"
                            class="rounded-xl font-mono uppercase"
                        />
                        <InputError :message="form.errors.ticker" />
                    </div>

                    <div v-else class="space-y-1.5">
                        <Label for="manual_value">Manual Valuation (IDR)</Label>
                        <MoneyInput
                            id="manual_value"
                            v-model="form.manual_value"
                            class="rounded-xl"
                        />
                        <InputError :message="form.errors.manual_value" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="notes">Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="w-full rounded-xl border border-input bg-background p-3 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <Checkbox id="is_active" v-model="form.is_active" />
                    <Label
                        for="is_active"
                        class="cursor-pointer text-xs font-normal"
                    >
                        Active (include in portfolio and net worth calculation)
                    </Label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link
                    :href="`/assets/${asset.id}`"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    Cancel
                </Link>
                <Button
                    type="submit"
                    class="rounded-xl bg-primary px-6 font-semibold text-primary-foreground shadow"
                    :disabled="form.processing"
                >
                    Save Changes
                </Button>
            </div>
        </form>
    </div>
</template>
