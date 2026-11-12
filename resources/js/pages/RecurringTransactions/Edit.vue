<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Repeat } from '@lucide/vue';
import { computed } from 'vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Recurring Transactions',
                href: '/recurring-transactions',
            },
            {
                title: 'Edit Template',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    recurring: {
        id: string;
        name: string;
        type: 'income' | 'expense';
        amount: number;
        currency: string;
        account_id: string;
        category_id: string | null;
        description: string;
        frequency: string;
        day_of_month: number | null;
        day_of_week: number | null;
        start_date: string;
        end_date: string | null;
        is_active: boolean;
        auto_confirm: boolean;
    };
    accounts: Array<{
        id: string;
        name: string;
        type?: string;
    }>;
    categories: Array<{
        id: string;
        name: string;
        type: string;
    }>;
}>();

const form = useForm({
    name: props.recurring.name,
    type: props.recurring.type,
    amount: props.recurring.amount,
    currency: props.recurring.currency,
    account_id: props.recurring.account_id,
    category_id: props.recurring.category_id || '',
    description: props.recurring.description,
    frequency: props.recurring.frequency,
    day_of_month: props.recurring.day_of_month || 25,
    day_of_week:
        props.recurring.day_of_week !== null ? props.recurring.day_of_week : 1,
    start_date: props.recurring.start_date,
    end_date: props.recurring.end_date || '',
    is_active: props.recurring.is_active,
    auto_confirm: props.recurring.auto_confirm,
});

const filteredCategories = computed(() => {
    return props.categories.filter((c) => c.type === form.type);
});

const submit = () => {
    form.put(`/recurring-transactions/${props.recurring.id}`);
};
</script>

<template>
    <Head :title="`Edit Template - ${props.recurring.name}`" />

    <div class="mx-auto max-w-2xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link
                href="/recurring-transactions"
                class="rounded-lg border p-2 text-muted-foreground transition-colors hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">
                    Edit Transaksi Berulang
                </h1>
                <p class="text-sm text-muted-foreground">
                    Perbarui nominal, frekuensi, atau akun sumber template.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-5 rounded-2xl border bg-card p-6 shadow-sm">
                <!-- Type Toggle: Income vs Expense -->
                <div class="flex rounded-lg bg-secondary p-1">
                    <button
                        type="button"
                        @click="form.type = 'expense'"
                        :class="[
                            'flex-1 rounded-md py-1.5 text-xs font-semibold transition-all',
                            form.type === 'expense'
                                ? 'bg-card text-foreground shadow-2xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Pengeluaran Rutin
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'income'"
                        :class="[
                            'flex-1 rounded-md py-1.5 text-xs font-semibold transition-all',
                            form.type === 'income'
                                ? 'bg-card text-foreground shadow-2xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Pemasukan Rutin
                    </button>
                </div>

                <!-- Template Name -->
                <div class="space-y-2">
                    <Label for="name">Nama Template</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Contoh: Gaji Bulanan, Sewa Kost, Spotify"
                        required
                    />
                    <p
                        v-if="form.errors.name"
                        class="text-xs font-medium text-rose-500"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Amount -->
                <div class="space-y-2">
                    <Label for="amount">Nominal</Label>
                    <MoneyInput
                        id="amount"
                        v-model="form.amount"
                        placeholder="Rp 0"
                        required
                    />
                    <p
                        v-if="form.errors.amount"
                        class="text-xs font-medium text-rose-500"
                    >
                        {{ form.errors.amount }}
                    </p>
                </div>

                <!-- Account & Category -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="account_id">Akun Sumber / Tujuan</Label>
                        <select
                            id="account_id"
                            v-model="form.account_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            required
                        >
                            <option value="" disabled>Pilih Akun</option>
                            <option
                                v-for="acc in props.accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.account_id"
                            class="text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.account_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="category_id">Kategori</Label>
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Tanpa Kategori</option>
                            <option
                                v-for="cat in filteredCategories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.category_id"
                            class="text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.category_id }}
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description"
                        >Catatan / Deskripsi Transaksi</Label
                    >
                    <Input
                        id="description"
                        v-model="form.description"
                        placeholder="Keterangan singkat saat transaksi dibuat"
                    />
                </div>

                <!-- Frequency Settings -->
                <div class="space-y-4 border-t pt-4">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <Repeat class="h-4 w-4 text-primary" />
                        Jadwal Pengulangan
                    </h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="frequency">Frekuensi</Label>
                            <select
                                id="frequency"
                                v-model="form.frequency"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                required
                            >
                                <option value="daily">
                                    Setiap Hari (Harian)
                                </option>
                                <option value="weekly">
                                    Setiap Minggu (Mingguan)
                                </option>
                                <option value="biweekly">
                                    Setiap 2 Minggu (Bi-Weekly)
                                </option>
                                <option value="monthly">
                                    Setiap Bulan (Bulanan)
                                </option>
                                <option value="yearly">
                                    Setiap Tahun (Tahunan)
                                </option>
                            </select>
                        </div>

                        <!-- Monthly: Day of Month -->
                        <div
                            v-if="form.frequency === 'monthly'"
                            class="space-y-2"
                        >
                            <Label for="day_of_month"
                                >Tanggal Eksekusi (1-28)</Label
                            >
                            <Input
                                id="day_of_month"
                                type="number"
                                min="1"
                                max="28"
                                v-model.number="form.day_of_month"
                                required
                            />
                        </div>

                        <!-- Weekly: Day of Week -->
                        <div
                            v-if="form.frequency === 'weekly'"
                            class="space-y-2"
                        >
                            <Label for="day_of_week">Hari</Label>
                            <select
                                id="day_of_week"
                                v-model.number="form.day_of_week"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option :value="1">Senin</option>
                                <option :value="2">Selasa</option>
                                <option :value="3">Rabu</option>
                                <option :value="4">Kamis</option>
                                <option :value="5">Jumat</option>
                                <option :value="6">Sabtu</option>
                                <option :value="0">Minggu</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="start_date">Mulai Berlaku</Label>
                            <Input
                                id="start_date"
                                type="date"
                                v-model="form.start_date"
                                required
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="end_date"
                                >Berakhir Pada (Opsional)</Label
                            >
                            <Input
                                id="end_date"
                                type="date"
                                v-model="form.end_date"
                            />
                        </div>
                    </div>

                    <!-- Auto-confirm & Active switches -->
                    <div class="space-y-2 pt-2">
                        <div class="flex items-center gap-2">
                            <input
                                id="auto_confirm"
                                type="checkbox"
                                v-model="form.auto_confirm"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <Label
                                for="auto_confirm"
                                class="cursor-pointer text-xs"
                            >
                                Otomatis konfirmasi transaksi (langsung masuk ke
                                saldo akun tanpa perlu konfirmasi manual)
                            </Label>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="is_active"
                                type="checkbox"
                                v-model="form.is_active"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                            />
                            <Label
                                for="is_active"
                                class="cursor-pointer text-xs"
                            >
                                Template aktif (siap dijalankan oleh penjadwal
                                otomatis)
                            </Label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    href="/recurring-transactions"
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                >
                    Batal
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing || !form.name || !form.amount"
                    class="min-w-32"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Perbarui Template' }}
                </Button>
            </div>
        </form>
    </div>
</template>
