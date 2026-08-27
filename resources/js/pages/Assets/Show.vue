<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    TrendingUp,
    TrendingDown,
    PlusCircle,
    MinusCircle,
    RefreshCw,
    Pencil,
    Trash2,
    Calendar,
    Wallet,
    CheckCircle2,
    Clock,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { computed, ref } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    asset: any;
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Portfolio & Assets',
                href: '/assets',
            },
            {
                title: 'Asset Details',
                href: '#',
            },
        ],
    },
});

// Modals State
const showPriceModal = ref(false);
const showBuyModal = ref(false);
const showSellModal = ref(false);
const showDeleteConfirm = ref(false);

// Forms
const priceForm = useForm({
    current_price: props.asset.current_price,
});

const buyForm = useForm({
    source_account_id: props.accounts[0]?.id || '',
    quantity: undefined as number | undefined,
    price_per_unit: props.asset.current_price || null,
    fees: 0,
    date: dayjs().format('YYYY-MM-DD'),
    notes: '',
});

const sellForm = useForm({
    destination_account_id: props.accounts[0]?.id || '',
    quantity: undefined as number | undefined,
    price_per_unit: props.asset.current_price || null,
    fees: 0,
    date: dayjs().format('YYYY-MM-DD'),
    notes: '',
});

const estimatedBuyTotal = computed(() => {
    const q = Number(buyForm.quantity) || 0;
    const p = Number(buyForm.price_per_unit) || 0;
    const f = Number(buyForm.fees) || 0;
    const multiplier = props.asset.type === 'stock' ? 100 : 1;

    return Math.round(q * p * multiplier) + f;
});

const estimatedSellNet = computed(() => {
    const q = Number(sellForm.quantity) || 0;
    const p = Number(sellForm.price_per_unit) || 0;
    const f = Number(sellForm.fees) || 0;
    const multiplier = props.asset.type === 'stock' ? 100 : 1;

    return Math.max(0, Math.round(q * p * multiplier) - f);
});

const submitPriceUpdate = () => {
    priceForm.patch(`/assets/${props.asset.id}/price`, {
        onSuccess: () => {
            showPriceModal.value = false;
        },
    });
};

const submitBuy = () => {
    buyForm.post(`/assets/${props.asset.id}/buy`, {
        onSuccess: () => {
            showBuyModal.value = false;
            buyForm.reset('quantity', 'notes');
        },
    });
};

const submitSell = () => {
    sellForm.post(`/assets/${props.asset.id}/sell`, {
        onSuccess: () => {
            showSellModal.value = false;
            sellForm.reset('quantity', 'notes');
        },
    });
};

const executeDelete = () => {
    router.delete(`/assets/${props.asset.id}`);
};

const formatDate = (dateStr: string) => {
    return dayjs(dateStr).format('D MMM YYYY');
};
</script>

<template>
    <Head :title="`${asset.name} - Asset Details`" />

    <div
        class="mx-auto flex h-full w-full max-w-6xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Top Back & Action Bar -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <Link
                    href="/assets"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    Back to Portfolio
                </Link>
                <div class="mt-2 flex items-center gap-3">
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ asset.name }}
                    </h1>
                    <span
                        v-if="asset.ticker"
                        class="rounded-lg bg-primary/10 px-2.5 py-1 font-mono text-sm font-bold text-primary"
                    >
                        {{ asset.ticker }}
                    </span>
                    <span
                        class="rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground uppercase"
                    >
                        {{ asset.type }}
                    </span>
                </div>
                <p
                    v-if="asset.platform"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    Managed on
                    <span class="font-medium text-foreground">{{
                        asset.platform
                    }}</span>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-if="asset.manual_value === null"
                    type="button"
                    variant="outline"
                    class="gap-1.5 rounded-xl border-border/80"
                    @click="showPriceModal = true"
                >
                    <RefreshCw class="h-4 w-4" />
                    Update Market Price
                </Button>

                <Button
                    v-if="asset.manual_value === null"
                    type="button"
                    class="gap-1.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-500"
                    @click="showBuyModal = true"
                >
                    <PlusCircle class="h-4 w-4" />
                    Buy / Add
                </Button>

                <Button
                    v-if="asset.manual_value === null"
                    type="button"
                    variant="outline"
                    :disabled="Number(asset.quantity) <= 0"
                    class="gap-1.5 rounded-xl border-rose-500/30 text-rose-600 hover:bg-rose-500/10 dark:text-rose-400"
                    @click="showSellModal = true"
                >
                    <MinusCircle class="h-4 w-4" />
                    Sell / Liquidate
                </Button>

                <Link
                    :href="`/assets/${asset.id}/edit`"
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-input bg-background px-3 text-xs font-medium shadow-sm transition-colors hover:bg-muted hover:text-foreground"
                >
                    <Pencil class="h-3.5 w-3.5" />
                    Edit
                </Link>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="rounded-xl text-rose-500 hover:bg-rose-500/10 hover:text-rose-600"
                    @click="showDeleteConfirm = true"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Current Total Value -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-medium text-muted-foreground uppercase"
                >
                    Current Total Value
                </span>
                <div class="mt-2 text-2xl font-extrabold tracking-tight">
                    <MoneyDisplay :amount="asset.current_value" />
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    Based on latest valuation
                </p>
            </div>

            <!-- Total Cost Basis -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-medium text-muted-foreground uppercase"
                >
                    Invested Capital (Cost Basis)
                </span>
                <div class="mt-2 text-2xl font-extrabold tracking-tight">
                    <MoneyDisplay :amount="asset.cost_basis" />
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    Weighted average buy cost
                </p>
            </div>

            <!-- Unrealized P/L -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-medium text-muted-foreground uppercase"
                >
                    Unrealized Gain / Loss
                </span>
                <div
                    :class="[
                        'mt-2 flex items-center gap-2 text-2xl font-extrabold tracking-tight',
                        asset.unrealized_pnl >= 0
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400',
                    ]"
                >
                    <TrendingUp
                        v-if="asset.unrealized_pnl >= 0"
                        class="h-6 w-6"
                    />
                    <TrendingDown v-else class="h-6 w-6" />
                    <MoneyDisplay :amount="asset.unrealized_pnl" />
                </div>
                <p
                    :class="[
                        'mt-2 text-xs font-semibold',
                        asset.unrealized_pnl >= 0
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400',
                    ]"
                >
                    {{ asset.unrealized_pnl >= 0 ? '+' : ''
                    }}{{ asset.unrealized_pnl_percentage }}% return
                </p>
            </div>

            <!-- Holdings / Price Details -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div v-if="asset.manual_value === null">
                    <span
                        class="text-xs font-medium text-muted-foreground uppercase"
                    >
                        Current Holdings
                    </span>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        {{ asset.quantity }}
                        <span class="text-sm font-normal text-muted-foreground">
                            {{ asset.type === 'stock' ? 'lots' : 'units' }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        <span v-if="asset.type === 'stock'">
                            ({{
                                new Intl.NumberFormat('id-ID').format(
                                    Number(asset.quantity) * 100,
                                )
                            }}
                            lembar) @ Rp
                            {{
                                new Intl.NumberFormat('id-ID').format(
                                    Number(asset.current_price),
                                )
                            }}
                            / lembar
                        </span>
                        <span v-else>
                            @
                            <MoneyDisplay :amount="asset.current_price" /> /
                            unit
                        </span>
                    </p>
                </div>
                <div v-else>
                    <span
                        class="text-xs font-medium text-muted-foreground uppercase"
                    >
                        Valuation Mode
                    </span>
                    <div class="mt-2 text-xl font-bold tracking-tight">
                        Manual / Fixed
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        Physical &amp; fixed asset
                    </p>
                </div>
            </div>
        </div>

        <!-- Notes Card if available -->
        <div
            v-if="asset.notes"
            class="rounded-2xl border bg-card p-5 shadow-sm"
        >
            <h4
                class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
            >
                Notes &amp; Strategy
            </h4>
            <p class="mt-2 text-sm whitespace-pre-wrap text-foreground/90">
                {{ asset.notes }}
            </p>
        </div>

        <!-- Transaction History -->
        <div class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold tracking-tight">
                        Trade History
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Recorded buy and sell transactions for this asset
                    </p>
                </div>
            </div>

            <div
                v-if="asset.transactions && asset.transactions.length > 0"
                class="mt-5 divide-y divide-border/60"
            >
                <div
                    v-for="tx in asset.transactions"
                    :key="tx.id"
                    class="flex flex-col items-start justify-between gap-3 py-3.5 sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'inline-flex items-center justify-center rounded-xl p-2 text-xs font-bold uppercase',
                                tx.type === 'buy'
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                            ]"
                        >
                            {{ tx.type }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-foreground">
                                {{ tx.quantity }} units @
                                <MoneyDisplay :amount="tx.price_per_unit" />
                            </p>
                            <div
                                class="flex items-center gap-2 text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-1">
                                    <Clock class="h-3 w-3" />
                                    {{ formatDate(tx.created_at) }}
                                </span>
                                <span v-if="tx.fees > 0">
                                    • Fees: <MoneyDisplay :amount="tx.fees" />
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-sm font-bold tabular-nums">
                            <MoneyDisplay :amount="tx.total_amount" />
                        </span>
                        <p class="text-[10px] text-muted-foreground uppercase">
                            {{
                                tx.type === 'buy'
                                    ? 'Total Cost'
                                    : 'Net Proceeds'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="py-10 text-center text-xs text-muted-foreground">
                No buy or sell trades recorded yet. Use the "Buy / Add" button
                to record your first transaction.
            </div>
        </div>

        <!-- Modal 1: Quick Price Update -->
        <Dialog v-model:open="showPriceModal">
            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Update Market Price</DialogTitle>
                    <DialogDescription>
                        Set the latest known market price per unit for
                        {{ asset.name }}.
                    </DialogDescription>
                </DialogHeader>

                <form
                    @submit.prevent="submitPriceUpdate"
                    class="space-y-4 py-2"
                >
                    <div class="space-y-1.5">
                        <Label for="update_price">
                            {{
                                asset.type === 'stock'
                                    ? 'New Market Price (Per Lembar)'
                                    : 'New Market Price (IDR)'
                            }}
                        </Label>
                        <MoneyInput
                            id="update_price"
                            v-model="priceForm.current_price"
                            :allow-decimal="true"
                            :max-decimals="4"
                            placeholder="0"
                            required
                        />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showPriceModal = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="priceForm.processing">
                            Save Price
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal 2: Buy / Add Asset -->
        <Dialog v-model:open="showBuyModal">
            <DialogContent class="rounded-2xl sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Buy / Add {{ asset.name }}</DialogTitle>
                    <DialogDescription>
                        Record a purchase. Funds will be deducted from your
                        chosen cash/bank account.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitBuy" class="space-y-4 py-2">
                    <!-- Source Account -->
                    <div class="space-y-1.5">
                        <Label for="source_account"
                            >Pay from Cash / Bank Account *</Label
                        >
                        <select
                            id="source_account"
                            v-model="buyForm.source_account_id"
                            required
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }} (Saldo: Rp
                                {{
                                    acc.current_balance.toLocaleString('id-ID')
                                }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="buy_qty">
                                {{
                                    asset.type === 'stock'
                                        ? 'Quantity (Lots) *'
                                        : 'Quantity *'
                                }}
                            </Label>
                            <Input
                                id="buy_qty"
                                v-model.number="buyForm.quantity"
                                type="number"
                                step="any"
                                min="0.00000001"
                                placeholder="0"
                                required
                                class="rounded-xl"
                            />
                            <p
                                v-if="
                                    asset.type === 'stock' && buyForm.quantity
                                "
                                class="text-[10px] text-muted-foreground"
                            >
                                =
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Number(buyForm.quantity) * 100,
                                    )
                                }}
                                lembar
                            </p>
                            <p
                                v-if="
                                    asset.type === 'stock' && buyForm.quantity
                                "
                                class="text-[10px] text-muted-foreground"
                            >
                                =
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Number(buyForm.quantity) * 100,
                                    )
                                }}
                                lembar
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="buy_price">
                                {{
                                    asset.type === 'stock'
                                        ? 'Price Per Lembar *'
                                        : 'Price Per Unit *'
                                }}
                            </Label>
                            <MoneyInput
                                id="buy_price"
                                v-model="buyForm.price_per_unit"
                                :allow-decimal="true"
                                :max-decimals="4"
                                placeholder="0"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="buy_fees">Transaction Fees</Label>
                            <MoneyInput
                                id="buy_fees"
                                v-model="buyForm.fees"
                                placeholder="0"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="buy_date">Transaction Date</Label>
                            <Input
                                id="buy_date"
                                v-model="buyForm.date"
                                type="date"
                                required
                                class="rounded-xl"
                            />
                        </div>
                    </div>

                    <!-- Live Calculation Summary -->
                    <div
                        class="flex items-center justify-between rounded-xl bg-muted/50 p-3 text-xs font-medium"
                    >
                        <span class="text-muted-foreground"
                            >Estimated Total Deducted:</span
                        >
                        <span class="text-sm font-bold text-foreground">
                            <MoneyDisplay :amount="estimatedBuyTotal" />
                        </span>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showBuyModal = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="bg-emerald-600 text-white hover:bg-emerald-500"
                            :disabled="buyForm.processing"
                        >
                            Confirm Purchase
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal 3: Sell Asset -->
        <Dialog v-model:open="showSellModal">
            <DialogContent class="rounded-2xl sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Sell / Liquidate {{ asset.name }}</DialogTitle>
                    <DialogDescription>
                        Record a sale. Proceeds will be credited to your chosen
                        cash/bank account.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitSell" class="space-y-4 py-2">
                    <!-- Destination Account -->
                    <div class="space-y-1.5">
                        <Label for="dest_account">Deposit Proceeds To *</Label>
                        <select
                            id="dest_account"
                            v-model="sellForm.destination_account_id"
                            required
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm focus-visible:ring-1 focus-visible:ring-ring"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="sell_qty">
                                {{
                                    asset.type === 'stock'
                                        ? `Lots to Sell (Max: ${asset.quantity} lots) *`
                                        : `Quantity to Sell (Max: ${asset.quantity}) *`
                                }}
                            </Label>
                            <Input
                                id="sell_qty"
                                v-model.number="sellForm.quantity"
                                type="number"
                                step="any"
                                min="0.00000001"
                                :max="Number(asset.quantity)"
                                placeholder="0"
                                required
                                class="rounded-xl"
                            />
                            <p
                                v-if="
                                    asset.type === 'stock' && sellForm.quantity
                                "
                                class="text-[10px] text-muted-foreground"
                            >
                                =
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Number(sellForm.quantity) * 100,
                                    )
                                }}
                                lembar
                            </p>
                            <p
                                v-if="
                                    asset.type === 'stock' && sellForm.quantity
                                "
                                class="text-[10px] text-muted-foreground"
                            >
                                =
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Number(sellForm.quantity) * 100,
                                    )
                                }}
                                lembar
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="sell_price">
                                {{
                                    asset.type === 'stock'
                                        ? 'Selling Price Per Lembar *'
                                        : 'Selling Price Per Unit *'
                                }}
                            </Label>
                            <MoneyInput
                                id="sell_price"
                                v-model="sellForm.price_per_unit"
                                :allow-decimal="true"
                                :max-decimals="4"
                                placeholder="0"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="sell_fees"
                                >Broker / Platform Fees</Label
                            >
                            <MoneyInput
                                id="sell_fees"
                                v-model="sellForm.fees"
                                placeholder="0"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="sell_date">Transaction Date</Label>
                            <Input
                                id="sell_date"
                                v-model="sellForm.date"
                                type="date"
                                required
                                class="rounded-xl"
                            />
                        </div>
                    </div>

                    <!-- Live Calculation Summary -->
                    <div
                        class="flex items-center justify-between rounded-xl bg-muted/50 p-3 text-xs font-medium"
                    >
                        <span class="text-muted-foreground"
                            >Estimated Net Proceeds Added:</span
                        >
                        <span
                            class="text-sm font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            +<MoneyDisplay :amount="estimatedSellNet" />
                        </span>
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showSellModal = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="bg-rose-600 text-white hover:bg-rose-500"
                            :disabled="sellForm.processing"
                        >
                            Confirm Sale
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal 4: Delete Confirm -->
        <Dialog v-model:open="showDeleteConfirm">
            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Remove Asset from Portfolio?</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove {{ asset.name }}? This
                        will remove the asset from your active net worth
                        calculations.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showDeleteConfirm = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Delete Asset
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
