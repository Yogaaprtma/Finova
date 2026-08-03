<script setup lang="ts">
import {
    ArrowLeftRight,
    ArrowRight,
    Building2,
    CheckCircle2,
    Lock,
    PieChart,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Wallet,
    Zap,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import {
    hideOverlayElement,
    markOnboardingCompletedInSession,
} from '@/lib/overlayTransition';

interface OnboardingSlide {
    id: 'wealth' | 'ledger' | 'security';
    badge: string;
    title: string;
    description: string;
}

const emit = defineEmits<{
    closing: [];
    complete: [target?: 'login' | 'register'];
}>();

const slides: OnboardingSlide[] = [
    {
        id: 'wealth',
        badge: 'Unified Wealth OS',
        title: 'All Your Assets in One Clear View',
        description:
            'Track cash, bank accounts, e-wallets, stocks, mutual funds, and crypto together in one real-time net worth dashboard.',
    },
    {
        id: 'ledger',
        badge: 'Fast & Accurate Ledger',
        title: 'Record Daily Flows in Under 10 Seconds',
        description:
            'Log income, expenses, and inter-account transfers effortlessly—backed by automated double-entry accounting precision.',
    },
    {
        id: 'security',
        badge: 'Insights & Bank-Grade Privacy',
        title: 'Smarter Decisions, Protected by Design',
        description:
            'Visualize monthly cash flow and asset allocation while keeping your financial records secured with 256-bit encryption and passkeys.',
    },
];

const overlayRef = ref<HTMLElement | null>(null);
const scrollerRef = ref<HTMLElement | null>(null);
const activeIndex = ref(0);
const isCompleting = ref(false);

const isLastSlide = computed(() => activeIndex.value === slides.length - 1);

const stepCounter = computed(
    () => `0${activeIndex.value + 1} / 0${slides.length}`,
);

const prefersReducedMotion = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
};

const handleScroll = () => {
    const el = scrollerRef.value;

    if (!el || el.clientWidth === 0) {
        return;
    }

    const nextIndex = Math.round(el.scrollLeft / el.clientWidth);
    const clampedIndex = Math.max(0, Math.min(slides.length - 1, nextIndex));

    if (clampedIndex !== activeIndex.value) {
        activeIndex.value = clampedIndex;
    }
};

const scrollToSlide = (index: number) => {
    const el = scrollerRef.value;
    const clampedIndex = Math.max(0, Math.min(slides.length - 1, index));

    activeIndex.value = clampedIndex;

    if (!el) {
        return;
    }

    el.scrollTo({
        left: clampedIndex * el.clientWidth,
        behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    });
};

const finishOnboarding = async (target?: 'login' | 'register') => {
    if (isCompleting.value) {
        return;
    }

    isCompleting.value = true;

    markOnboardingCompletedInSession(true);
    emit('closing');

    await hideOverlayElement(overlayRef.value, 480);

    emit('complete', target);
};

const handlePrimaryAction = () => {
    if (isLastSlide.value) {
        void finishOnboarding('login');

        return;
    }

    scrollToSlide(activeIndex.value + 1);
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowRight') {
        event.preventDefault();

        if (!isLastSlide.value) {
            scrollToSlide(activeIndex.value + 1);
        }
    } else if (event.key === 'ArrowLeft') {
        event.preventDefault();

        if (activeIndex.value > 0) {
            scrollToSlide(activeIndex.value - 1);
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        void finishOnboarding();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div
        ref="overlayRef"
        role="region"
        aria-label="Finova Onboarding Introduction"
        class="finova-onboarding-overlay fixed inset-0 z-40 flex min-h-svh w-full flex-col justify-between overflow-hidden bg-background text-foreground select-none"
    >
        <!-- Decorative Ambient Background -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="absolute -top-24 left-1/2 h-[340px] w-[340px] -translate-x-1/2 rounded-full bg-blue-500/12 blur-[90px] sm:h-[460px] sm:w-[460px] dark:bg-blue-600/18"
            />
            <div
                class="absolute bottom-16 left-1/4 h-[240px] w-[240px] rounded-full bg-indigo-500/10 blur-[80px] dark:bg-indigo-500/15"
            />
        </div>

        <!-- Top Navigation Header -->
        <header
            class="relative z-10 mx-auto flex w-full max-w-xl items-center justify-between px-6 pt-6 sm:px-8 sm:pt-8"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex size-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md ring-1 shadow-blue-500/25 ring-white/20"
                >
                    <AppLogoIcon class="size-5" />
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold tracking-tight">Finova</span>
                    <span
                        class="rounded-full border border-border/80 bg-muted/70 px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground tabular-nums"
                    >
                        {{ stepCounter }}
                    </span>
                </div>
            </div>

            <button
                type="button"
                class="cursor-pointer rounded-full border border-border/70 bg-card/70 px-4 py-1.5 text-xs font-semibold text-muted-foreground backdrop-blur-md transition-all hover:border-blue-500/30 hover:text-foreground active:scale-95"
                @click="finishOnboarding()"
            >
                Skip
            </button>
        </header>

        <!-- Horizontal Swipeable Scroll-Snap Carousel -->
        <main
            ref="scrollerRef"
            class="relative z-10 flex w-full flex-1 snap-x snap-mandatory [scrollbar-width:none] items-center overflow-x-auto scroll-smooth [&::-webkit-scrollbar]:hidden"
            @scroll.passive="handleScroll"
        >
            <section
                v-for="(slide, index) in slides"
                :key="slide.id"
                :aria-hidden="activeIndex !== index"
                class="finova-onboarding-slide flex h-full w-full shrink-0 snap-center snap-always flex-col items-center justify-center px-6 py-4 sm:px-8"
            >
                <div class="mx-auto flex w-full max-w-md flex-col items-center">
                    <!-- ILLUSTRATION SHOWCASE CARD PER SLIDE -->
                    <div
                        class="relative mb-7 flex h-[240px] w-full items-center justify-center sm:mb-8 sm:h-[268px]"
                    >
                        <!-- Soft Halo Behind Card -->
                        <div
                            class="pointer-events-none absolute size-52 rounded-full bg-gradient-to-tr from-blue-500/20 via-indigo-500/15 to-sky-400/20 blur-2xl dark:from-blue-500/25 dark:via-indigo-500/20 dark:to-cyan-400/15"
                        />

                        <!-- SLIDE 1 ILLUSTRATION: UNIFIED NET WORTH -->
                        <div
                            v-if="slide.id === 'wealth'"
                            class="relative w-full max-w-[320px]"
                        >
                            <!-- Floating Satellite Chip (Top Right) -->
                            <div
                                class="onboarding-float-slow absolute -top-3 -right-2 z-20 flex items-center gap-1.5 rounded-2xl border border-emerald-500/25 bg-white/90 px-3 py-1.5 text-xs font-semibold text-emerald-600 shadow-lg backdrop-blur-md dark:bg-slate-900/90 dark:text-emerald-400"
                            >
                                <TrendingUp class="size-3.5" />
                                <span>+12.4% MTD</span>
                            </div>

                            <!-- Main Net Worth Glass Card -->
                            <div
                                class="relative rounded-3xl border border-slate-200/80 bg-white/90 p-5 shadow-xl shadow-blue-500/10 backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/90"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-medium text-muted-foreground"
                                    >
                                        Total Net Worth
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2.5 py-0.5 text-[10px] font-semibold text-blue-600 dark:text-blue-400"
                                    >
                                        IDR • Live
                                    </span>
                                </div>

                                <div
                                    class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums sm:text-[26px]"
                                >
                                    Rp 248.500.000
                                </div>

                                <!-- Multi-Asset Allocation Bar -->
                                <div
                                    class="mt-4 flex h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                                >
                                    <div class="h-full w-[45%] bg-blue-600" />
                                    <div class="h-full w-[35%] bg-indigo-500" />
                                    <div class="h-full w-[20%] bg-sky-400" />
                                </div>

                                <!-- Asset Pills -->
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <div
                                        class="rounded-2xl bg-slate-50 p-2.5 dark:bg-slate-800/80"
                                    >
                                        <Building2
                                            class="size-4 text-blue-600 dark:text-blue-400"
                                        />
                                        <p
                                            class="mt-1.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            Bank
                                        </p>
                                        <p class="text-xs font-bold">45%</p>
                                    </div>
                                    <div
                                        class="rounded-2xl bg-slate-50 p-2.5 dark:bg-slate-800/80"
                                    >
                                        <TrendingUp
                                            class="size-4 text-indigo-600 dark:text-indigo-400"
                                        />
                                        <p
                                            class="mt-1.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            Invest
                                        </p>
                                        <p class="text-xs font-bold">35%</p>
                                    </div>
                                    <div
                                        class="rounded-2xl bg-slate-50 p-2.5 dark:bg-slate-800/80"
                                    >
                                        <Wallet
                                            class="size-4 text-sky-500 dark:text-sky-400"
                                        />
                                        <p
                                            class="mt-1.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            Wallet
                                        </p>
                                        <p class="text-xs font-bold">20%</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SLIDE 2 ILLUSTRATION: DOUBLE-ENTRY LEDGER -->
                        <div
                            v-else-if="slide.id === 'ledger'"
                            class="relative w-full max-w-[320px]"
                        >
                            <!-- Speed Badge -->
                            <div
                                class="onboarding-float-slow absolute -top-3 right-0 z-20 flex items-center gap-1.5 rounded-2xl border border-amber-500/25 bg-white/90 px-3 py-1.5 text-xs font-semibold text-amber-600 shadow-lg backdrop-blur-md dark:bg-slate-900/90 dark:text-amber-400"
                            >
                                <Zap class="size-3.5" />
                                <span>&lt; 10s Quick Log</span>
                            </div>

                            <!-- Ledger Card -->
                            <div
                                class="relative space-y-3 rounded-3xl border border-slate-200/80 bg-white/90 p-5 shadow-xl shadow-blue-500/10 backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/90"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-semibold text-foreground"
                                    >
                                        Double-Entry Journal
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                                    >
                                        <CheckCircle2 class="size-3.5" />
                                        Balanced
                                    </span>
                                </div>

                                <!-- Entry 1 -->
                                <div
                                    class="flex items-center justify-between rounded-2xl bg-slate-50 px-3.5 py-2.5 dark:bg-slate-800/80"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex size-8 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                                        >
                                            <TrendingUp class="size-4" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold">
                                                Monthly Salary
                                            </p>
                                            <p
                                                class="text-[10px] text-muted-foreground"
                                            >
                                                BCA Main • Income
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-emerald-600 tabular-nums dark:text-emerald-400"
                                    >
                                        +Rp 18,5jt
                                    </span>
                                </div>

                                <!-- Entry 2 -->
                                <div
                                    class="flex items-center justify-between rounded-2xl bg-slate-50 px-3.5 py-2.5 dark:bg-slate-800/80"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex size-8 items-center justify-center rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400"
                                        >
                                            <ArrowLeftRight class="size-4" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold">
                                                Auto-Invest Mutual Fund
                                            </p>
                                            <p
                                                class="text-[10px] text-muted-foreground"
                                            >
                                                BCA → Bibit Portfolio
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="text-xs font-bold text-blue-600 tabular-nums dark:text-blue-400"
                                    >
                                        Rp 5,0jt
                                    </span>
                                </div>

                                <!-- Ledger Proof Footer -->
                                <div
                                    class="flex items-center justify-between border-t border-border/60 pt-2.5 text-[11px] text-muted-foreground"
                                >
                                    <span>Debit = Credit Integrity</span>
                                    <span class="font-semibold text-foreground">
                                        100% Verified
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- SLIDE 3 ILLUSTRATION: INSIGHTS & SECURITY -->
                        <div v-else class="relative w-full max-w-[320px]">
                            <!-- Floating Lock Pill -->
                            <div
                                class="onboarding-float-slow absolute -top-3 -right-1 z-20 flex items-center gap-1.5 rounded-2xl border border-blue-500/25 bg-white/90 px-3 py-1.5 text-xs font-semibold text-blue-600 shadow-lg backdrop-blur-md dark:bg-slate-900/90 dark:text-blue-400"
                            >
                                <Lock class="size-3.5" />
                                <span>Passkey &amp; OAuth Ready</span>
                            </div>

                            <!-- Analytics & Security Card -->
                            <div
                                class="relative rounded-3xl border border-slate-200/80 bg-white/90 p-5 shadow-xl shadow-blue-500/10 backdrop-blur-xl dark:border-white/10 dark:bg-slate-900/90"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <PieChart
                                            class="size-4 text-blue-600 dark:text-blue-400"
                                        />
                                        <span class="text-xs font-semibold">
                                            Cash Flow &amp; Savings
                                        </span>
                                    </div>
                                    <span
                                        class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        38% Saved
                                    </span>
                                </div>

                                <!-- Mini Bar Visualization -->
                                <div
                                    class="mt-4 flex h-24 items-end justify-between gap-2.5 rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80"
                                >
                                    <div
                                        class="flex flex-1 flex-col items-center gap-1"
                                    >
                                        <div
                                            class="h-10 w-full rounded-t-lg bg-blue-400/50"
                                        />
                                        <span
                                            class="text-[9px] text-muted-foreground"
                                            >Jul</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-1 flex-col items-center gap-1"
                                    >
                                        <div
                                            class="h-12 w-full rounded-t-lg bg-blue-500/65"
                                        />
                                        <span
                                            class="text-[9px] text-muted-foreground"
                                            >Aug</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-1 flex-col items-center gap-1"
                                    >
                                        <div
                                            class="h-14 w-full rounded-t-lg bg-blue-500/80"
                                        />
                                        <span
                                            class="text-[9px] text-muted-foreground"
                                            >Sep</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-1 flex-col items-center gap-1"
                                    >
                                        <div
                                            class="h-16 w-full rounded-t-lg bg-gradient-to-t from-blue-600 to-indigo-500 shadow-sm shadow-blue-500/30"
                                        />
                                        <span
                                            class="text-[9px] font-semibold text-foreground"
                                            >Oct</span
                                        >
                                    </div>
                                </div>

                                <!-- Security Shield Banner -->
                                <div
                                    class="mt-3.5 flex items-center gap-2.5 rounded-2xl border border-blue-500/15 bg-blue-500/8 px-3.5 py-2.5"
                                >
                                    <ShieldCheck
                                        class="size-4 shrink-0 text-blue-600 dark:text-blue-400"
                                    />
                                    <span
                                        class="text-[11px] font-medium text-foreground/90"
                                    >
                                        256-Bit Bank-Grade Security • Zero Data
                                        Selling
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SLIDE BADGE & COPY -->
                    <span
                        class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-600 dark:text-blue-400"
                    >
                        <Sparkles class="size-3" />
                        <span>{{ slide.badge }}</span>
                    </span>

                    <h2
                        class="max-w-sm text-center text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        {{ slide.title }}
                    </h2>

                    <p
                        class="mt-3 max-w-sm text-center text-sm leading-relaxed text-muted-foreground sm:text-[15px]"
                    >
                        {{ slide.description }}
                    </p>
                </div>
            </section>
        </main>

        <!-- Bottom Pagination Dots & Primary CTA -->
        <footer
            class="relative z-10 mx-auto flex w-full max-w-md flex-col items-center gap-5 px-6 pb-8 sm:px-8 sm:pb-10"
        >
            <!-- Interactive Pill Pagination Dots -->
            <div
                role="tablist"
                aria-label="Onboarding slides"
                class="flex items-center justify-center gap-2"
            >
                <button
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeIndex === index"
                    :aria-label="`Go to slide ${index + 1}: ${slide.title}`"
                    :class="[
                        'h-2 cursor-pointer rounded-full transition-all duration-300',
                        activeIndex === index
                            ? 'w-8 bg-blue-600 shadow-sm shadow-blue-500/40 dark:bg-blue-500'
                            : 'w-2 bg-slate-300 hover:bg-slate-400 dark:bg-slate-700 dark:hover:bg-slate-600',
                    ]"
                    @click="scrollToSlide(index)"
                />
            </div>

            <!-- Primary Action Button (Next / Get Started) -->
            <div class="flex w-full flex-col gap-2.5">
                <Button
                    type="button"
                    class="flex h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-blue-600 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:bg-blue-500 hover:shadow-blue-500/40 active:scale-[0.99]"
                    @click="handlePrimaryAction"
                >
                    <span>{{ isLastSlide ? 'Get Started' : 'Next' }}</span>
                    <ArrowRight class="size-4" />
                </Button>

                <button
                    v-if="isLastSlide"
                    type="button"
                    class="cursor-pointer py-1 text-center text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    @click="finishOnboarding('register')"
                >
                    New to Finova?
                    <span
                        class="ml-0.5 font-semibold text-blue-600 dark:text-blue-400"
                    >
                        Create free account
                    </span>
                </button>
            </div>
        </footer>
    </div>
</template>

<style>
.finova-onboarding-overlay {
    opacity: 1;
    translate: 0 0;
    transition:
        display 0.45s,
        opacity 0.45s cubic-bezier(0.22, 1, 0.36, 1),
        translate 0.45s cubic-bezier(0.22, 1, 0.36, 1);
    transition-behavior: allow-discrete;
}

.finova-onboarding-overlay:where([hidden], [data-closing]) {
    opacity: 0;
    translate: 0 -14px;
}

.finova-onboarding-overlay[hidden] {
    display: none;
}

@media (prefers-reduced-motion: reduce) {
    .finova-onboarding-overlay {
        translate: none;
        transition-duration: 0.1s;
    }

    .finova-onboarding-overlay:where([hidden], [data-closing]) {
        translate: none;
    }
}
</style>

<style scoped>
@keyframes finova-carousel-focus {
    0% {
        opacity: 0.45;
        scale: 0.92;
    }
    50% {
        opacity: 1;
        scale: 1;
    }
    100% {
        opacity: 0.45;
        scale: 0.92;
    }
}

@keyframes float-badge {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.onboarding-float-slow {
    animation: float-badge 4s ease-in-out infinite;
}

@supports ((animation-timeline: view()) and (animation-range: entry)) {
    .finova-onboarding-slide {
        animation: finova-carousel-focus auto linear both;
        animation-timeline: view(inline);
    }
}

@media (prefers-reduced-motion: reduce) {
    .finova-onboarding-slide,
    .onboarding-float-slow {
        animation: none;
    }
}
</style>
