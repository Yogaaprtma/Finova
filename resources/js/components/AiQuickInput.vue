<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    SendHorizontal,
    Loader2,
    CheckCircle2,
    AlertTriangle,
    XCircle,
    Pencil,
    X,
    ArrowDownRight,
    ArrowUpRight,
    ArrowRightLeft,
    CalendarDays,
    Tag,
    Wallet,
    Check,
} from '@lucide/vue';
import { ref, reactive, computed, onBeforeUnmount } from 'vue';

const props = defineProps<{
    accounts?: Array<{ id: string; name: string; type?: string }>;
    categories?: Array<{
        id: string;
        name: string;
        slug?: string;
        type?: string;
    }>;
}>();

const emit = defineEmits<{
    (e: 'transaction-created'): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const inputMessage = ref('');
const isParsing = ref(false);
const isConfirming = ref(false);
const parseError = ref<string | null>(null);
const parsedResult = ref<any | null>(null);
const logId = ref<string | null>(null);
const savedMessage = ref<string | null>(null);
let savedTimer: ReturnType<typeof setTimeout> | null = null;

// Editable preview state
const editForm = reactive({
    type: 'expense',
    amount: 0,
    date: new Date().toISOString().split('T')[0],
    description: '',
    category_id: '',
    account_id: '',
    from_account_id: '',
    to_account_id: '',
    notes: '',
});

const isEditing = ref(false);

const confidenceLevel = computed(() => {
    if (!parsedResult.value) {
        return 'none';
    }

    const conf = parsedResult.value.confidence ?? 0;

    if (conf >= 0.85) {
        return 'high';
    }

    if (conf >= 0.5) {
        return 'medium';
    }

    return 'low';
});

const confidencePercent = computed(() =>
    Math.round((parsedResult.value?.confidence ?? 0) * 100),
);

const quickPrompts = [
    'Beli nasi padang 25rb',
    'Bensin motor 30rb pake GoPay',
    'Gajian 8jt masuk BCA',
    'Transfer BCA ke Mandiri 500rb',
];

const typeMeta = computed(() => {
    switch (editForm.type) {
        case 'income':
            return {
                label: 'Pemasukan',
                sign: '+',
                icon: ArrowUpRight,
                strip: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                amount: 'text-emerald-600 dark:text-emerald-400',
            };
        case 'transfer':
            return {
                label: 'Transfer',
                sign: '',
                icon: ArrowRightLeft,
                strip: 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
                amount: 'text-sky-600 dark:text-sky-400',
            };
        default:
            return {
                label: 'Pengeluaran',
                sign: '-',
                icon: ArrowDownRight,
                strip: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
                amount: 'text-rose-600 dark:text-rose-400',
            };
    }
});

const accountName = (id: string) =>
    props.accounts?.find((a) => a.id === id)?.name ?? '';

const categoryLabel = computed(
    () =>
        props.categories?.find((c) => c.id === editForm.category_id)?.name ||
        'Tanpa kategori',
);

const accountLabel = computed(() => {
    if (editForm.type === 'transfer') {
        const from = accountName(editForm.from_account_id) || '?';
        const to = accountName(editForm.to_account_id) || '?';

        return `${from} → ${to}`;
    }

    return accountName(editForm.account_id) || 'Pilih akun';
});

const dateLabel = computed(() => {
    const d = new Date(`${editForm.date}T00:00:00`);

    if (Number.isNaN(d.getTime())) {
        return editForm.date;
    }

    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
});

const setPrompt = (prompt: string) => {
    inputMessage.value = prompt;
    handleParse();
};

const handleParse = async () => {
    const msg = inputMessage.value.trim();

    if (!msg) {
        inputRef.value?.focus();

        return;
    }

    if (isParsing.value) {
        return;
    }

    isParsing.value = true;
    parseError.value = null;
    parsedResult.value = null;
    savedMessage.value = null;
    isEditing.value = false;

    try {
        const response = await fetch('/ai/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
            },
            body: JSON.stringify({ message: msg }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal memproses pesan via AI');
        }

        const res = data.data.result;
        parsedResult.value = res;
        logId.value = data.data.log_id;

        // Populate edit form with AI results & matched IDs
        editForm.type = res.transaction_type || 'expense';
        editForm.amount = res.amount || 0;
        editForm.date = res.date || new Date().toISOString().split('T')[0];
        editForm.description = res.description || msg;
        editForm.notes = res.notes || '';
        editForm.category_id = data.data.matched_category_id || '';
        editForm.account_id =
            data.data.matched_account_id || props.accounts?.[0]?.id || '';
        editForm.from_account_id =
            data.data.matched_from_account_id || props.accounts?.[0]?.id || '';
        editForm.to_account_id =
            data.data.matched_to_account_id ||
            props.accounts?.[1]?.id ||
            props.accounts?.[0]?.id ||
            '';

        // If confidence is medium, open edit form automatically
        if (res.confidence < 0.85) {
            isEditing.value = true;
        }
    } catch (err: any) {
        parseError.value =
            err.message || 'Terjadi kesalahan saat memproses input AI.';
    } finally {
        isParsing.value = false;
    }
};

const handleConfirm = async () => {
    if (isConfirming.value) {
        return;
    }

    isConfirming.value = true;
    parseError.value = null;

    try {
        const payload: Record<string, any> = {
            type: editForm.type,
            amount: editForm.amount,
            date: editForm.date,
            description: editForm.description,
            notes: editForm.notes || null,
            log_id: logId.value,
        };

        if (editForm.type === 'transfer') {
            payload.from_account_id = editForm.from_account_id;
            payload.to_account_id = editForm.to_account_id;
        } else {
            payload.account_id = editForm.account_id;
            payload.category_id = editForm.category_id || null;
        }

        const response = await fetch('/ai/confirm', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal menyimpan transaksi.');
        }

        // Reset state
        inputMessage.value = '';
        parsedResult.value = null;
        logId.value = null;
        isEditing.value = false;

        savedMessage.value = 'Transaksi berhasil disimpan.';

        if (savedTimer) {
            clearTimeout(savedTimer);
        }

        savedTimer = setTimeout(() => (savedMessage.value = null), 4000);

        // Emit and reload Inertia
        emit('transaction-created');
        router.reload();
    } catch (err: any) {
        parseError.value = err.message || 'Gagal menyimpan transaksi.';
    } finally {
        isConfirming.value = false;
    }
};

const handleCancel = async () => {
    if (logId.value) {
        try {
            await fetch('/ai/reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        (
                            document.querySelector(
                                'meta[name="csrf-token"]',
                            ) as HTMLMetaElement
                        )?.content || '',
                },
                body: JSON.stringify({ log_id: logId.value }),
            });
        } catch {
            // Ignore reject errors
        }
    }

    parsedResult.value = null;
    logId.value = null;
    isEditing.value = false;
    parseError.value = null;
};

const formatRupiah = (val: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const filteredCategories = computed(() => {
    if (!props.categories) {
        return [];
    }

    if (editForm.type === 'transfer') {
        return [];
    }

    return props.categories.filter((c) => !c.type || c.type === editForm.type);
});

onBeforeUnmount(() => {
    if (savedTimer) {
        clearTimeout(savedTimer);
    }
});

// 16px on touch screens prevents iOS zoom-on-focus; 44px keeps touch targets usable.
const fieldClass =
    'mt-1.5 h-11 w-full rounded-xl border border-input bg-background px-3 text-base sm:h-10 sm:text-sm focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:outline-none';
const labelClass = 'text-xs font-medium text-muted-foreground';
</script>

<template>
    <section
        aria-labelledby="ai-quick-input-title"
        class="rounded-xl border bg-card"
    >
        <div class="p-4 sm:p-5">
            <!-- Header -->
            <div class="mb-3">
                <h2
                    id="ai-quick-input-title"
                    class="text-sm font-semibold text-foreground"
                >
                    Catat cepat
                </h2>
                <p class="text-xs text-muted-foreground">
                    Tulis transaksi seperti biasa, kolomnya terisi otomatis.
                </p>
            </div>

            <!-- Input composer -->
            <form @submit.prevent="handleParse" class="relative" role="search">
                <label for="ai-quick-input" class="sr-only">
                    Ceritakan transaksi Anda
                </label>
                <input
                    id="ai-quick-input"
                    ref="inputRef"
                    v-model="inputMessage"
                    type="text"
                    name="message"
                    autocomplete="off"
                    enterkeyhint="send"
                    :disabled="isParsing"
                    placeholder="Contoh: beli kopi 20rb pakai GoPay"
                    class="h-11 w-full rounded-lg border border-input bg-background py-2 pr-14 pl-3.5 text-base transition placeholder:text-muted-foreground/70 focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60 sm:text-sm"
                />
                <button
                    type="submit"
                    :disabled="isParsing"
                    aria-label="Proses dengan AI"
                    class="absolute top-1/2 right-1 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md bg-primary text-primary-foreground transition after:absolute after:-inset-1 after:content-[''] hover:bg-primary/90 active:scale-95 disabled:opacity-60"
                >
                    <Loader2 v-if="isParsing" class="h-4 w-4 animate-spin" />
                    <SendHorizontal v-else class="h-4 w-4" aria-hidden="true" />
                </button>
            </form>

            <!-- Quick suggestions (scrollable chips on mobile) -->
            <div
                v-if="!parsedResult && !isParsing"
                class="mt-3 -mr-4 flex [scrollbar-width:none] items-center gap-2 overflow-x-auto pr-4 pb-1 sm:mr-0 sm:flex-wrap sm:overflow-visible sm:pr-0 [&::-webkit-scrollbar]:hidden"
            >
                <span class="shrink-0 text-xs text-muted-foreground">
                    Coba:
                </span>
                <button
                    v-for="prompt in quickPrompts"
                    :key="prompt"
                    type="button"
                    @click="setPrompt(prompt)"
                    class="inline-flex h-8 shrink-0 items-center rounded-md bg-muted px-3 text-xs text-muted-foreground transition hover:bg-accent hover:text-foreground active:scale-95"
                >
                    {{ prompt }}
                </button>
            </div>

            <!-- Loading state -->
            <div
                v-if="isParsing"
                role="status"
                class="mt-4 flex items-center gap-2.5 text-sm text-muted-foreground"
            >
                <Loader2 class="h-4 w-4 shrink-0 animate-spin" />
                <span>Membaca transaksi…</span>
            </div>

            <!-- Success message -->
            <div
                v-if="savedMessage"
                role="status"
                class="mt-4 flex items-center gap-2.5 rounded-xl border border-emerald-500/25 bg-emerald-500/10 p-3.5 text-sm font-medium text-emerald-700 dark:text-emerald-300"
            >
                <CheckCircle2 class="h-4 w-4 shrink-0" />
                {{ savedMessage }}
            </div>

            <!-- Error Message -->
            <div
                v-if="parseError"
                role="alert"
                class="mt-4 flex items-start gap-2.5 rounded-xl border border-destructive/25 bg-destructive/10 p-3.5 text-sm text-destructive"
            >
                <XCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ parseError }}</span>
            </div>

            <!-- Parsed Result: receipt-style preview -->
            <article
                v-if="parsedResult"
                aria-live="polite"
                class="mt-4 overflow-hidden rounded-2xl border bg-background shadow-sm"
            >
                <!-- Type strip + confidence -->
                <header
                    class="flex items-center justify-between gap-2 px-4 py-2.5"
                    :class="typeMeta.strip"
                >
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <component
                            :is="typeMeta.icon"
                            class="h-4 w-4"
                            aria-hidden="true"
                        />
                        {{ typeMeta.label }}
                    </div>

                    <div class="flex items-center gap-1">
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-background/70 px-2 py-0.5 text-[11px] font-medium"
                            :class="{
                                'text-emerald-600 dark:text-emerald-400':
                                    confidenceLevel === 'high',
                                'text-amber-600 dark:text-amber-400':
                                    confidenceLevel === 'medium',
                                'text-rose-600 dark:text-rose-400':
                                    confidenceLevel === 'low',
                            }"
                        >
                            <CheckCircle2
                                v-if="confidenceLevel === 'high'"
                                class="h-3 w-3"
                            />
                            <AlertTriangle v-else class="h-3 w-3" />
                            {{
                                confidenceLevel === 'high'
                                    ? 'AI yakin'
                                    : confidenceLevel === 'medium'
                                      ? 'Mohon dicek'
                                      : 'Kurang yakin'
                            }}
                            · {{ confidencePercent }}%
                        </span>
                        <button
                            type="button"
                            @click="handleCancel"
                            aria-label="Batalkan"
                            class="relative flex h-8 w-8 items-center justify-center rounded-lg text-current/70 transition after:absolute after:-inset-1 after:content-[''] hover:bg-background/60 hover:text-current"
                        >
                            <X class="h-4 w-4" aria-hidden="true" />
                        </button>
                    </div>
                </header>

                <!-- Summary -->
                <div class="space-y-3 px-4 pt-4 pb-3">
                    <div>
                        <p
                            class="text-3xl font-extrabold tracking-tight tabular-nums"
                            :class="typeMeta.amount"
                        >
                            {{ typeMeta.sign
                            }}{{ formatRupiah(editForm.amount) }}
                        </p>
                        <p
                            class="mt-1 text-sm break-words text-muted-foreground"
                        >
                            {{ editForm.description || '—' }}
                        </p>
                    </div>

                    <ul class="flex flex-wrap gap-2 text-xs">
                        <li
                            class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 font-medium text-foreground/80"
                        >
                            <CalendarDays
                                class="h-3.5 w-3.5 text-muted-foreground"
                                aria-hidden="true"
                            />
                            {{ dateLabel }}
                        </li>
                        <li
                            v-if="editForm.type !== 'transfer'"
                            class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 font-medium text-foreground/80"
                        >
                            <Tag
                                class="h-3.5 w-3.5 text-muted-foreground"
                                aria-hidden="true"
                            />
                            {{ categoryLabel }}
                        </li>
                        <li
                            class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 font-medium text-foreground/80"
                        >
                            <Wallet
                                class="h-3.5 w-3.5 text-muted-foreground"
                                aria-hidden="true"
                            />
                            {{ accountLabel }}
                        </li>
                    </ul>

                    <button
                        type="button"
                        @click="isEditing = !isEditing"
                        :aria-expanded="isEditing"
                        aria-controls="ai-edit-panel"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-primary transition hover:bg-primary/5"
                    >
                        <Pencil class="h-3.5 w-3.5" aria-hidden="true" />
                        {{ isEditing ? 'Tutup detail' : 'Ubah detail' }}
                    </button>
                </div>

                <!-- Edit panel -->
                <div
                    v-if="isEditing"
                    id="ai-edit-panel"
                    class="space-y-3 border-t bg-muted/30 px-4 py-4"
                >
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label for="ai-type" :class="labelClass">
                                Tipe transaksi
                            </label>
                            <select
                                id="ai-type"
                                v-model="editForm.type"
                                :class="fieldClass"
                            >
                                <option value="expense">Pengeluaran</option>
                                <option value="income">Pemasukan</option>
                                <option value="transfer">
                                    Transfer antar akun
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="ai-amount" :class="labelClass">
                                Nominal (Rp)
                            </label>
                            <input
                                id="ai-amount"
                                v-model.number="editForm.amount"
                                type="number"
                                inputmode="numeric"
                                min="1"
                                :class="[fieldClass, 'font-semibold']"
                            />
                        </div>

                        <div>
                            <label for="ai-date" :class="labelClass">
                                Tanggal
                            </label>
                            <input
                                id="ai-date"
                                v-model="editForm.date"
                                type="date"
                                :class="fieldClass"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="ai-desc" :class="labelClass">
                                Deskripsi
                            </label>
                            <input
                                id="ai-desc"
                                v-model="editForm.description"
                                type="text"
                                :class="fieldClass"
                            />
                        </div>

                        <div v-if="editForm.type !== 'transfer'">
                            <label for="ai-category" :class="labelClass">
                                Kategori
                            </label>
                            <select
                                id="ai-category"
                                v-model="editForm.category_id"
                                :class="fieldClass"
                            >
                                <option value="">
                                    Pilih kategori (opsional)
                                </option>
                                <option
                                    v-for="cat in filteredCategories"
                                    :key="cat.id"
                                    :value="cat.id"
                                >
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>

                        <div v-else>
                            <label for="ai-to" :class="labelClass">
                                Rekening tujuan
                            </label>
                            <select
                                id="ai-to"
                                v-model="editForm.to_account_id"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="acc in props.accounts"
                                    :key="acc.id"
                                    :value="acc.id"
                                >
                                    {{ acc.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-if="editForm.type !== 'transfer'">
                            <label for="ai-account" :class="labelClass">
                                Akun / sumber dana
                            </label>
                            <select
                                id="ai-account"
                                v-model="editForm.account_id"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="acc in props.accounts"
                                    :key="acc.id"
                                    :value="acc.id"
                                >
                                    {{ acc.name }}
                                </option>
                            </select>
                        </div>
                        <div v-else>
                            <label for="ai-from" :class="labelClass">
                                Rekening sumber
                            </label>
                            <select
                                id="ai-from"
                                v-model="editForm.from_account_id"
                                :class="fieldClass"
                            >
                                <option
                                    v-for="acc in props.accounts"
                                    :key="acc.id"
                                    :value="acc.id"
                                >
                                    {{ acc.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <footer
                    class="flex flex-col-reverse gap-2 border-t px-4 py-3 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        @click="handleCancel"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-border px-4 text-sm font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground sm:h-10"
                    >
                        Batalkan
                    </button>
                    <button
                        type="button"
                        @click="handleConfirm"
                        :disabled="isConfirming || editForm.amount <= 0"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-md shadow-primary/25 transition hover:bg-primary/90 active:scale-[0.98] disabled:opacity-50 sm:h-10"
                    >
                        <Loader2
                            v-if="isConfirming"
                            class="h-4 w-4 animate-spin"
                        />
                        <Check v-else class="h-4 w-4" aria-hidden="true" />
                        Simpan transaksi
                    </button>
                </footer>
            </article>
        </div>
    </section>
</template>
