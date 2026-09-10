<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Sparkles,
    Send,
    Loader2,
    CheckCircle2,
    AlertTriangle,
    XCircle,
    ArrowRight,
    Edit2,
    X,
} from '@lucide/vue';
import { ref, reactive, computed } from 'vue';

const props = defineProps<{
    accounts?: Array<{ id: string; name: string; type?: string }>;
    categories?: Array<{ id: string; name: string; slug?: string; type?: string }>;
}>();

const emit = defineEmits<{
    (e: 'transaction-created'): void;
}>();

const inputMessage = ref('');
const isParsing = ref(false);
const isConfirming = ref(false);
const parseError = ref<string | null>(null);
const parsedResult = ref<any | null>(null);
const logId = ref<string | null>(null);

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
    if (!parsedResult.value) return 'none';
    const conf = parsedResult.value.confidence ?? 0;
    if (conf >= 0.85) return 'high';
    if (conf >= 0.50) return 'medium';
    return 'low';
});

const quickPrompts = [
    'Beli nasi padang 25rb',
    'Bensin motor 30rb pake GoPay',
    'Gajian 8jt masuk BCA',
    'Transfer BCA ke Mandiri 500rb',
];

const setPrompt = (prompt: string) => {
    inputMessage.value = prompt;
    handleParse();
};

const handleParse = async () => {
    const msg = inputMessage.value.trim();
    if (!msg || isParsing.value) return;

    isParsing.value = true;
    parseError.value = null;
    parsedResult.value = null;
    isEditing.value = false;

    try {
        const response = await fetch('/ai/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
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
        editForm.account_id = data.data.matched_account_id || (props.accounts?.[0]?.id || '');
        editForm.from_account_id = data.data.matched_from_account_id || (props.accounts?.[0]?.id || '');
        editForm.to_account_id = data.data.matched_to_account_id || (props.accounts?.[1]?.id || props.accounts?.[0]?.id || '');

        // If confidence is medium, open edit form automatically
        if (res.confidence < 0.85) {
            isEditing.value = true;
        }
    } catch (err: any) {
        parseError.value = err.message || 'Terjadi kesalahan saat memproses input AI.';
    } finally {
        isParsing.value = false;
    }
};

const handleConfirm = async () => {
    if (isConfirming.value) return;
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
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
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

        // Emit and reload Inertia
        emit('transaction-created');
        router.reload({ preserveScroll: true });
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
                        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
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
    }).format(val);
};

const filteredCategories = computed(() => {
    if (!props.categories) return [];
    if (editForm.type === 'transfer') return [];
    return props.categories.filter((c) => !c.type || c.type === editForm.type);
});
</script>

<template>
    <div class="rounded-2xl border border-primary/20 bg-gradient-to-r from-card via-card to-primary/5 p-4 shadow-sm sm:p-5">
        <!-- Header -->
        <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <Sparkles class="h-4 w-4" />
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-foreground">AI Natural Transaction Parser</h3>
                    <p class="text-xs text-muted-foreground">Ketik transaksi dalam bahasa sehari-hari, AI akan mengekstrak otomatis</p>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-[10px] font-medium text-primary">
                Gemini 2.0 AI
            </span>
        </div>

        <!-- Input Bar -->
        <form @submit.prevent="handleParse" class="relative">
            <div class="relative flex items-center">
                <input
                    v-model="inputMessage"
                    type="text"
                    :disabled="isParsing"
                    placeholder="Contoh: beli nasi goreng 15rb pake gopay, atau transfer bca ke mandiri 500rb..."
                    class="h-11 w-full rounded-xl border border-input bg-background/80 py-2 pr-12 pl-4 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-50"
                />
                <button
                    type="submit"
                    :disabled="!inputMessage.trim() || isParsing"
                    class="absolute right-1.5 flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-40"
                >
                    <Loader2 v-if="isParsing" class="h-4 w-4 animate-spin" />
                    <Send v-else class="h-3.5 w-3.5" />
                </button>
            </div>
        </form>

        <!-- Quick Suggestions -->
        <div v-if="!parsedResult && !isParsing" class="mt-2.5 flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-[11px] text-muted-foreground">Coba:</span>
            <button
                v-for="(prompt, idx) in quickPrompts"
                :key="idx"
                type="button"
                @click="setPrompt(prompt)"
                class="rounded-full border border-border/60 bg-muted/40 px-2.5 py-0.5 text-[11px] text-muted-foreground transition hover:border-primary/40 hover:bg-primary/5 hover:text-foreground"
            >
                {{ prompt }}
            </button>
        </div>

        <!-- Error Message -->
        <div v-if="parseError" class="mt-3 flex items-center gap-2 rounded-xl border border-destructive/20 bg-destructive/10 p-3 text-xs text-destructive">
            <XCircle class="h-4 w-4 shrink-0" />
            <span>{{ parseError }}</span>
        </div>

        <!-- Parsed Result Preview Card -->
        <div
            v-if="parsedResult"
            class="mt-4 rounded-xl border p-4 transition-all"
            :class="{
                'border-emerald-500/30 bg-emerald-500/5': confidenceLevel === 'high',
                'border-amber-500/30 bg-amber-500/5': confidenceLevel === 'medium',
                'border-rose-500/30 bg-rose-500/5': confidenceLevel === 'low',
            }"
        >
            <!-- Preview Header -->
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CheckCircle2 v-if="confidenceLevel === 'high'" class="h-4 w-4 text-emerald-500" />
                    <AlertTriangle v-else-if="confidenceLevel === 'medium'" class="h-4 w-4 text-amber-500" />
                    <XCircle v-else class="h-4 w-4 text-rose-500" />
                    <span
                        class="text-xs font-semibold"
                        :class="{
                            'text-emerald-500': confidenceLevel === 'high',
                            'text-amber-500': confidenceLevel === 'medium',
                            'text-rose-500': confidenceLevel === 'low',
                        }"
                    >
                        {{ confidenceLevel === 'high' ? 'AI Percaya Diri (Tinggi)' : confidenceLevel === 'medium' ? 'AI Perlu Konfirmasi (Sedang)' : 'Perlu Klarifikasi (Rendah)' }}
                    </span>
                    <span class="text-[10px] text-muted-foreground">
                        ({{ Math.round((parsedResult.confidence ?? 0) * 100) }}% confidence)
                    </span>
                </div>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="isEditing = !isEditing"
                        class="flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                    >
                        <Edit2 class="h-3 w-3" />
                        <span>{{ isEditing ? 'Tutup Edit' : 'Edit Detail' }}</span>
                    </button>
                    <button
                        type="button"
                        @click="handleCancel"
                        class="rounded-md p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- Summary View (when not editing) -->
            <div v-if="!isEditing" class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                <div class="rounded-lg bg-background/60 p-2.5">
                    <div class="text-[10px] font-medium text-muted-foreground uppercase">Tipe</div>
                    <div class="mt-0.5 font-semibold capitalize text-foreground">{{ editForm.type }}</div>
                </div>
                <div class="rounded-lg bg-background/60 p-2.5">
                    <div class="text-[10px] font-medium text-muted-foreground uppercase">Nominal</div>
                    <div class="mt-0.5 font-bold text-foreground">{{ formatRupiah(editForm.amount) }}</div>
                </div>
                <div class="rounded-lg bg-background/60 p-2.5">
                    <div class="text-[10px] font-medium text-muted-foreground uppercase">Deskripsi</div>
                    <div class="mt-0.5 truncate font-medium text-foreground">{{ editForm.description || '-' }}</div>
                </div>
                <div class="rounded-lg bg-background/60 p-2.5">
                    <div class="text-[10px] font-medium text-muted-foreground uppercase">Tanggal</div>
                    <div class="mt-0.5 font-medium text-foreground">{{ editForm.date }}</div>
                </div>
            </div>

            <!-- Full Editable Form (when editing or medium confidence) -->
            <div v-else class="space-y-3 pt-1">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="text-[11px] font-medium text-muted-foreground">Tipe Transaksi</label>
                        <select
                            v-model="editForm.type"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        >
                            <option value="expense">Pengeluaran (Expense)</option>
                            <option value="income">Pemasukan (Income)</option>
                            <option value="transfer">Transfer Antar Akun</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-muted-foreground">Nominal (Rp)</label>
                        <input
                            v-model.number="editForm.amount"
                            type="number"
                            min="1"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs font-semibold focus:border-primary focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="text-[11px] font-medium text-muted-foreground">Tanggal</label>
                        <input
                            v-model="editForm.date"
                            type="date"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-[11px] font-medium text-muted-foreground">Deskripsi Transaksi</label>
                        <input
                            v-model="editForm.description"
                            type="text"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        />
                    </div>

                    <!-- Category (if income or expense) -->
                    <div v-if="editForm.type !== 'transfer'">
                        <label class="text-[11px] font-medium text-muted-foreground">Kategori</label>
                        <select
                            v-model="editForm.category_id"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        >
                            <option value="">Pilih Kategori (Opsional)</option>
                            <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Transfer destination account -->
                    <div v-else>
                        <label class="text-[11px] font-medium text-muted-foreground">Rekening Tujuan</label>
                        <select
                            v-model="editForm.to_account_id"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        >
                            <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Account selection -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div v-if="editForm.type !== 'transfer'">
                        <label class="text-[11px] font-medium text-muted-foreground">Akun / Sumber Dana</label>
                        <select
                            v-model="editForm.account_id"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        >
                            <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>
                    <div v-else>
                        <label class="text-[11px] font-medium text-muted-foreground">Rekening Sumber</label>
                        <select
                            v-model="editForm.from_account_id"
                            class="mt-1 h-9 w-full rounded-lg border border-input bg-background px-3 text-xs focus:border-primary focus:outline-none"
                        >
                            <option v-for="acc in props.accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 flex items-center justify-end gap-2 pt-2 border-t border-border/50">
                <button
                    type="button"
                    @click="handleCancel"
                    class="h-8 rounded-lg border border-border px-3 text-xs font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                >
                    Batalkan
                </button>
                <button
                    type="button"
                    @click="handleConfirm"
                    :disabled="isConfirming || editForm.amount <= 0"
                    class="flex h-8 items-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 disabled:opacity-50"
                >
                    <Loader2 v-if="isConfirming" class="h-3.5 w-3.5 animate-spin" />
                    <span v-else>Konfirmasi & Simpan Transaksi</span>
                    <ArrowRight v-if="!isConfirming" class="h-3 w-3" />
                </button>
            </div>
        </div>
    </div>
</template>

