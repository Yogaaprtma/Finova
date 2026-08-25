<script setup lang="ts">
import { Form, Head, usePage, Link } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Loader2,
    MessageSquare,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// 16px text on mobile prevents iOS Safari from zooming on focus.
const fieldClass = 'h-11 text-base md:h-10 md:text-sm';
const selectClass =
    'h-11 w-full appearance-none rounded-md border border-input bg-background pr-10 pl-3 text-base shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/30 focus-visible:outline-none md:h-10 md:text-sm';

const currencies = [
    { value: 'IDR', label: 'IDR – Indonesian Rupiah (Rp)' },
    { value: 'USD', label: 'USD – US Dollar ($)' },
    { value: 'EUR', label: 'EUR – Euro (€)' },
    { value: 'SGD', label: 'SGD – Singapore Dollar (S$)' },
];

const timezones = [
    { value: 'Asia/Jakarta', label: 'Asia/Jakarta (WIB, UTC+7)' },
    { value: 'Asia/Makassar', label: 'Asia/Makassar (WITA, UTC+8)' },
    { value: 'Asia/Jayapura', label: 'Asia/Jayapura (WIT, UTC+9)' },
    { value: 'UTC', label: 'UTC (Coordinated Universal Time)' },
];

const dateFormats = [
    { value: 'd/m/Y', label: 'DD/MM/YYYY (31/12/2026)' },
    { value: 'Y-m-d', label: 'YYYY-MM-DD (2026-12-31)' },
    { value: 'm/d/Y', label: 'MM/DD/YYYY (12/31/2026)' },
];

const currentCurrency = computed(() => user.value.default_currency ?? 'IDR');
const currentTimezone = computed(() => user.value.timezone ?? 'Asia/Jakarta');
const currentDateFormat = computed(() => user.value.date_format ?? 'd/m/Y');
</script>

<template>
    <Head title="Profile settings" />

    <div class="space-y-6">
        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing, recentlySuccessful }"
        >
            <!-- Personal information -->
            <section class="rounded-xl border bg-card text-card-foreground">
                <div class="border-b px-5 py-4 sm:px-6">
                    <h2 class="text-base font-semibold tracking-tight">
                        Personal information
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Your name, email, and WhatsApp number.
                    </p>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <!-- Name -->
                    <div class="space-y-2">
                        <Label for="name" class="text-sm font-medium">
                            Full name
                        </Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                            :class="fieldClass"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="space-y-2">
                        <Label for="email" class="text-sm font-medium">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="name@example.com"
                            :class="fieldClass"
                        />
                        <InputError :message="errors.email" />

                        <div
                            v-if="
                                page.props.mustVerifyEmail &&
                                !user.email_verified_at
                            "
                            class="flex gap-3 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-800 dark:text-amber-300"
                        >
                            <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                            <div class="space-y-1">
                                <p>
                                    Your email address is unverified.
                                    <Link
                                        :href="send()"
                                        as="button"
                                        class="inline-flex min-h-6 text-left font-medium underline underline-offset-2 hover:text-amber-950 dark:hover:text-amber-100"
                                    >
                                        Resend verification email
                                    </Link>
                                </p>
                                <p
                                    v-if="
                                        page.props.status ===
                                        'verification-link-sent'
                                    "
                                    class="font-medium text-emerald-700 dark:text-emerald-400"
                                >
                                    A new verification link has been sent to
                                    your email address.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp -->
                    <div class="space-y-2">
                        <div
                            class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1"
                        >
                            <Label
                                for="whatsapp_number"
                                class="text-sm font-medium"
                            >
                                WhatsApp number
                            </Label>
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                <MessageSquare class="size-3" />
                                AI bot integration
                            </span>
                        </div>
                        <Input
                            id="whatsapp_number"
                            type="tel"
                            inputmode="tel"
                            name="whatsapp_number"
                            autocomplete="tel"
                            :default-value="
                                (user.whatsapp_number as string) || ''
                            "
                            placeholder="e.g. 081234567890"
                            :class="fieldClass"
                        />
                        <InputError :message="errors.whatsapp_number" />
                        <p class="text-sm text-muted-foreground">
                            Tautkan nomor WhatsApp aktif untuk mencatat
                            pengeluaran/pemasukan otomatis lewat chat dengan
                            Finova AI bot.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Financial & regional preferences -->
            <section class="rounded-xl border bg-card text-card-foreground">
                <div class="border-b px-5 py-4 sm:px-6">
                    <h2 class="text-base font-semibold tracking-tight">
                        Financial &amp; regional preferences
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Currency, timezone, date format, and your monthly
                        budget cycle.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <!-- Currency -->
                    <div class="space-y-2">
                        <Label
                            for="default_currency"
                            class="text-sm font-medium"
                        >
                            Default currency
                        </Label>
                        <div class="relative">
                            <select
                                id="default_currency"
                                name="default_currency"
                                :class="selectClass"
                                required
                            >
                                <option
                                    v-for="opt in currencies"
                                    :key="opt.value"
                                    :value="opt.value"
                                    :selected="currentCurrency === opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <InputError :message="errors.default_currency" />
                    </div>

                    <!-- Timezone -->
                    <div class="space-y-2">
                        <Label for="timezone" class="text-sm font-medium">
                            Timezone
                        </Label>
                        <div class="relative">
                            <select
                                id="timezone"
                                name="timezone"
                                :class="selectClass"
                                required
                            >
                                <option
                                    v-for="opt in timezones"
                                    :key="opt.value"
                                    :value="opt.value"
                                    :selected="currentTimezone === opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <InputError :message="errors.timezone" />
                    </div>

                    <!-- Date format -->
                    <div class="space-y-2">
                        <Label for="date_format" class="text-sm font-medium">
                            Date format
                        </Label>
                        <div class="relative">
                            <select
                                id="date_format"
                                name="date_format"
                                :class="selectClass"
                                required
                            >
                                <option
                                    v-for="opt in dateFormats"
                                    :key="opt.value"
                                    :value="opt.value"
                                    :selected="currentDateFormat === opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <ChevronDown
                                class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                        </div>
                        <InputError :message="errors.date_format" />
                    </div>

                    <!-- Month start day -->
                    <div class="space-y-2">
                        <Label
                            for="month_start_day"
                            class="text-sm font-medium"
                        >
                            Month start day
                        </Label>
                        <Input
                            id="month_start_day"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="28"
                            name="month_start_day"
                            :default-value="String(user.month_start_day || 1)"
                            required
                            placeholder="1"
                            :class="fieldClass"
                        />
                        <InputError :message="errors.month_start_day" />
                        <p class="text-sm text-muted-foreground">
                            Tanggal awal siklus bulanan (1–28), misalnya
                            tanggal gajian.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Single save action for the whole form -->
            <div
                class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end"
            >
                <Transition
                    enter-active-class="transition-opacity duration-150"
                    leave-active-class="transition-opacity duration-300"
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="recentlySuccessful"
                        class="inline-flex items-center justify-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400"
                        role="status"
                    >
                        <Check class="size-4" />
                        Changes saved
                    </p>
                </Transition>

                <Button
                    type="submit"
                    :disabled="processing"
                    class="h-11 w-full gap-2 px-6 text-sm font-medium sm:h-10 sm:w-auto"
                    data-test="update-profile-button"
                >
                    <Loader2 v-if="processing" class="size-4 animate-spin" />
                    <span>{{ processing ? 'Saving…' : 'Save changes' }}</span>
                </Button>
            </div>
        </Form>

        <!-- Danger zone -->
        <DeleteUser />
    </div>
</template>