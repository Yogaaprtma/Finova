<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import {
    hideOverlayElement,
    markSplashPlayedInSession,
} from '@/lib/overlayTransition';

const emit = defineEmits<{
    closing: [];
    finish: [];
}>();

const overlayRef = ref<HTMLElement | null>(null);
const isFinishing = ref(false);
const isTabHidden = ref(false);
let splashTimer: ReturnType<typeof setTimeout> | null = null;

const dismissSplash = async () => {
    if (isFinishing.value) {
        return;
    }

    isFinishing.value = true;

    if (splashTimer !== null) {
        clearTimeout(splashTimer);
        splashTimer = null;
    }

    markSplashPlayedInSession(true);
    emit('closing');

    await hideOverlayElement(overlayRef.value, 550);

    emit('finish');
};

const startSplashCountdown = () => {
    if (splashTimer !== null || isFinishing.value) {
        return;
    }

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
    const durationMs = prefersReducedMotion ? 850 : 2150;

    splashTimer = setTimeout(() => {
        void dismissSplash();
    }, durationMs);
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        isTabHidden.value = false;
        startSplashCountdown();
    }
};

onMounted(() => {
    if (document.visibilityState === 'hidden') {
        isTabHidden.value = true;
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return;
    }

    startSplashCountdown();
});

onBeforeUnmount(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange);

    if (splashTimer !== null) {
        clearTimeout(splashTimer);
        splashTimer = null;
    }
});
</script>

<template>
    <div
        ref="overlayRef"
        role="status"
        aria-live="polite"
        aria-label="Loading Finova Personal Wealth OS"
        :data-paused="isTabHidden ? '' : undefined"
        class="finova-splash-overlay fixed inset-0 z-50 flex cursor-pointer flex-col items-center justify-between overflow-hidden bg-[#060b18] px-6 py-10 text-white select-none sm:py-14"
        @click="dismissSplash"
    >
        <!-- Ambient Radial Glows & Vertical Light Streaks -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div
                class="splash-aura absolute top-1/2 left-1/2 h-[360px] w-[360px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-600/25 blur-[95px] sm:h-[480px] sm:w-[480px]"
            />
            <div
                class="absolute top-1/3 left-1/2 h-[220px] w-[220px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-indigo-500/20 blur-[70px]"
            />
            <div
                class="absolute top-12 left-1/5 h-36 w-[1.5px] bg-gradient-to-b from-transparent via-blue-400/30 to-transparent"
            />
            <div
                class="absolute right-1/5 bottom-16 h-44 w-[1.5px] bg-gradient-to-b from-transparent via-indigo-400/30 to-transparent"
            />
        </div>

        <!-- Top Subtle Spacer -->
        <div class="relative z-10 flex items-center gap-2 opacity-70">
            <span
                class="inline-block size-1.5 rounded-full bg-blue-400 shadow-[0_0_8px_#60a5fa]"
            />
            <span
                class="text-[11px] font-medium tracking-[0.2em] text-blue-200/80 uppercase"
            >
                Wealth OS
            </span>
        </div>

        <!-- Centerpiece Animated Logo + Brand Name -->
        <div class="relative z-10 flex flex-col items-center">
            <!-- Squircle Badge + Ripple Rings -->
            <div class="relative flex items-center justify-center">
                <span
                    class="splash-ring-1 pointer-events-none absolute size-24 rounded-[30px] border border-blue-400/35 sm:size-28"
                />
                <span
                    class="splash-ring-2 pointer-events-none absolute size-32 rounded-[36px] border border-indigo-400/20 sm:size-36"
                />

                <div
                    class="splash-logo-badge relative flex size-22 items-center justify-center rounded-[28px] bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 shadow-[0_20px_60px_rgba(59,99,246,0.55)] ring-1 ring-white/30 sm:size-24"
                >
                    <!-- Inner glass highlight -->
                    <div
                        class="pointer-events-none absolute inset-0 rounded-[28px] bg-gradient-to-b from-white/25 via-transparent to-transparent"
                    />

                    <!-- Animated Finova Chart Vector -->
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.1"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="relative z-10 size-11 text-white drop-shadow-[0_4px_12px_rgba(255,255,255,0.3)] sm:size-12"
                    >
                        <path
                            pathLength="100"
                            d="M3 3v18h18"
                            class="splash-axis-path"
                        />
                        <path
                            pathLength="100"
                            d="M7 13l3-3 4 4 5-5"
                            class="splash-trend-path"
                        />
                        <circle
                            cx="19"
                            cy="9"
                            r="2.1"
                            fill="currentColor"
                            stroke="none"
                            class="splash-apex-dot"
                        />
                    </svg>
                </div>
            </div>

            <!-- Wordmark & Tagline -->
            <div class="mt-7 flex flex-col items-center text-center">
                <h1
                    class="splash-wordmark bg-gradient-to-r from-white via-blue-50 to-blue-200 bg-clip-text text-4xl font-extrabold tracking-tight text-transparent sm:text-5xl"
                >
                    Finova
                </h1>
                <p
                    class="splash-tagline mt-2.5 text-xs font-medium tracking-[0.18em] text-blue-200/75 uppercase sm:text-sm"
                >
                    Personal Wealth &amp; Finance OS
                </p>
            </div>
        </div>

        <!-- Bottom Progress Indicator & Security Trust -->
        <div
            class="relative z-10 flex w-full max-w-xs flex-col items-center gap-4"
        >
            <div
                class="h-1 w-36 overflow-hidden rounded-full bg-white/10 ring-1 ring-white/5"
            >
                <div
                    class="splash-progress-bar h-full w-full origin-left rounded-full bg-gradient-to-r from-blue-400 via-blue-500 to-indigo-400"
                />
            </div>

            <div
                class="splash-footer flex items-center gap-1.5 text-[11px] font-medium text-slate-400"
            >
                <ShieldCheck class="size-3.5 text-blue-400" />
                <span>256-Bit Encrypted • Tap anywhere to continue</span>
            </div>
        </div>
    </div>
</template>

<style>
.finova-splash-overlay {
    opacity: 1;
    scale: 1;
    transition:
        display 0.5s,
        opacity 0.5s cubic-bezier(0.22, 1, 0.36, 1),
        scale 0.5s cubic-bezier(0.22, 1, 0.36, 1);
    transition-behavior: allow-discrete;
}

.finova-splash-overlay:where([hidden], [data-closing]) {
    opacity: 0;
    scale: 1.04;
}

.finova-splash-overlay[hidden] {
    display: none;
}

@media (prefers-reduced-motion: reduce) {
    .finova-splash-overlay {
        scale: none;
        transition-duration: 0.1s;
    }

    .finova-splash-overlay:where([hidden], [data-closing]) {
        scale: none;
    }
}
</style>

<style scoped>
@keyframes badge-pop {
    0% {
        opacity: 0;
        transform: scale(0.65) translateY(14px);
    }
    70% {
        opacity: 1;
        transform: scale(1.04) translateY(-2px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes stroke-draw {
    0% {
        stroke-dashoffset: 100;
    }
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes dot-pop {
    0% {
        opacity: 0;
        transform: scale(0);
    }
    70% {
        opacity: 1;
        transform: scale(1.35);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes fade-up {
    0% {
        opacity: 0;
        transform: translateY(12px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes ring-pulse {
    0% {
        opacity: 0;
        transform: scale(0.8);
    }
    45% {
        opacity: 0.7;
    }
    100% {
        opacity: 0;
        transform: scale(1.28);
    }
}

@keyframes progress-fill {
    0% {
        transform: scaleX(0.05);
    }
    100% {
        transform: scaleX(1);
    }
}

@keyframes aura-breathe {
    0%,
    100% {
        opacity: 0.75;
        transform: scale(0.95);
    }
    50% {
        opacity: 1;
        transform: scale(1.08);
    }
}

.splash-logo-badge {
    animation: badge-pop 0.65s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

.splash-axis-path {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: stroke-draw 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.2s forwards;
}

.splash-trend-path {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: stroke-draw 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.48s forwards;
}

.splash-apex-dot {
    transform-box: fill-box;
    transform-origin: center;
    opacity: 0;
    animation: dot-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.92s forwards;
}

.splash-wordmark {
    opacity: 0;
    animation: fade-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.42s forwards;
}

.splash-tagline {
    opacity: 0;
    animation: fade-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) 0.58s forwards;
}

.splash-footer {
    opacity: 0;
    animation: fade-up 0.5s ease-out 0.65s forwards;
}

.splash-ring-1 {
    animation: ring-pulse 2s cubic-bezier(0.22, 1, 0.36, 1) 0.35s infinite;
}

.splash-ring-2 {
    animation: ring-pulse 2s cubic-bezier(0.22, 1, 0.36, 1) 0.7s infinite;
}

.splash-progress-bar {
    animation: progress-fill 1.95s cubic-bezier(0.33, 1, 0.68, 1) forwards;
}

.splash-aura {
    animation: aura-breathe 3s ease-in-out infinite;
}

[data-paused] .splash-logo-badge,
[data-paused] .splash-axis-path,
[data-paused] .splash-trend-path,
[data-paused] .splash-apex-dot,
[data-paused] .splash-wordmark,
[data-paused] .splash-tagline,
[data-paused] .splash-footer,
[data-paused] .splash-ring-1,
[data-paused] .splash-ring-2,
[data-paused] .splash-progress-bar,
[data-paused] .splash-aura {
    animation-play-state: paused;
}

@media (prefers-reduced-motion: reduce) {
    .splash-logo-badge,
    .splash-axis-path,
    .splash-trend-path,
    .splash-apex-dot,
    .splash-wordmark,
    .splash-tagline,
    .splash-footer,
    .splash-progress-bar,
    .splash-aura {
        animation: none;
        opacity: 1;
        stroke-dashoffset: 0;
        transform: none;
    }

    .splash-ring-1,
    .splash-ring-2 {
        display: none;
    }
}
</style>
