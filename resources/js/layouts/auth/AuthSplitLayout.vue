<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ShieldCheck, Sparkles } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import OnboardingScreen from '@/components/auth/OnboardingScreen.vue';
import SplashScreen from '@/components/auth/SplashScreen.vue';
import {
    hasCompletedOnboardingInSession,
    hasPlayedSplashInSession,
    markOnboardingCompletedInSession,
    markSplashPlayedInSession,
} from '@/lib/overlayTransition';
import { home, login, register } from '@/routes';

const props = defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();

const currentUrl = computed(() => page.url || '');
const currentComponent = computed(() => page.component || '');

const isLogin = computed(
    () =>
        currentUrl.value.startsWith('/login') ||
        currentComponent.value === 'auth/Login',
);

const isRegister = computed(
    () =>
        currentUrl.value.startsWith('/register') ||
        currentComponent.value === 'auth/Register',
);

const isAuthTab = computed(() => isLogin.value || isRegister.value);

const hasServerFeedback = computed(() => {
    const pageProps = page.props as Record<string, unknown>;
    const errors = (pageProps.errors as Record<string, unknown>) || {};

    return Object.keys(errors).length > 0 || Boolean(pageProps.status);
});

const resolveInitialIntroState = () => {
    if (
        typeof window === 'undefined' ||
        !isAuthTab.value ||
        hasServerFeedback.value
    ) {
        return {
            splash: false,
            onboarding: false,
            pending: false,
        };
    }

    const pending = !hasCompletedOnboardingInSession();

    if (!hasPlayedSplashInSession()) {
        return {
            splash: true,
            onboarding: false,
            pending,
        };
    }

    return {
        splash: false,
        onboarding: pending,
        pending,
    };
};

const initialIntro = resolveInitialIntroState();

const showSplash = ref(initialIntro.splash);
const showOnboarding = ref(initialIntro.onboarding);
const pendingOnboarding = ref(initialIntro.pending);
const isSplashClosing = ref(false);
const isOnboardingClosing = ref(false);

const isOverlayActive = computed(
    () => showSplash.value || showOnboarding.value,
);

const hideAuthContent = computed(
    () =>
        (showSplash.value && !isSplashClosing.value) ||
        (showOnboarding.value && !isOnboardingClosing.value),
);

const handleSplashClosing = () => {
    isSplashClosing.value = true;

    if (pendingOnboarding.value) {
        isOnboardingClosing.value = false;
        showOnboarding.value = true;
    }
};

const handleSplashFinish = () => {
    showSplash.value = false;
    isSplashClosing.value = false;

    if (pendingOnboarding.value) {
        showOnboarding.value = true;
    }
};

const handleOnboardingClosing = () => {
    isOnboardingClosing.value = true;
};

const handleOnboardingComplete = (target?: 'login' | 'register') => {
    pendingOnboarding.value = false;
    showOnboarding.value = false;
    isOnboardingClosing.value = false;

    if (target === 'register' && !isRegister.value) {
        router.visit(register().url, { preserveScroll: true });
    } else if (target === 'login' && !isLogin.value) {
        router.visit(login().url, { preserveScroll: true });
    }
};

const replayIntro = () => {
    markSplashPlayedInSession(false);
    markOnboardingCompletedInSession(false);
    isSplashClosing.value = false;
    isOnboardingClosing.value = false;
    showOnboarding.value = false;
    pendingOnboarding.value = true;
    showSplash.value = true;
};

onMounted(() => {
    document.documentElement.removeAttribute('data-auth-boot');
});

// Dynamic heading if not explicitly provided
const displayTitle = computed(() => {
    if (props.title) {
        return props.title;
    }

    if (isLogin.value) {
        return 'Welcome Back';
    }

    if (isRegister.value) {
        return 'Create an Account';
    }

    return 'Finova';
});

const displayDescription = computed(() => {
    if (props.description) {
        return props.description;
    }

    if (isLogin.value) {
        return 'Welcome back, please enter your details';
    }

    if (isRegister.value) {
        return 'Start managing your wealth and financial freedom today';
    }

    return '';
});
</script>

<template>
    <!-- 2. Swipeable Onboarding Screen (Second position right after Splash) -->
    <OnboardingScreen
        v-if="showOnboarding"
        @closing="handleOnboardingClosing"
        @complete="handleOnboardingComplete"
    />

    <!-- 1. Animated Splash Screen (First position on cold start, rendered on top) -->
    <SplashScreen
        v-if="showSplash"
        @closing="handleSplashClosing"
        @finish="handleSplashFinish"
    />

    <div
        :inert="isOverlayActive || undefined"
        :class="[
            'grid min-h-screen w-full bg-background text-foreground selection:bg-blue-500/20 lg:grid-cols-2',
            hideAuthContent
                ? 'pointer-events-none invisible opacity-0'
                : 'visible opacity-100',
        ]"
    >
        <!-- LEFT COLUMN: AUTH CONTENT & FORM -->
        <div
            class="flex min-h-svh w-full flex-col justify-between p-6 sm:p-10 lg:p-12 xl:p-16"
        >
            <!-- TOP BRAND HEADER -->
            <div class="flex items-center justify-between">
                <Link
                    :href="home()"
                    class="group flex items-center gap-2.5 transition-transform duration-200 active:scale-95"
                >
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-md ring-1 shadow-blue-500/25 ring-white/20 transition-all duration-300 group-hover:shadow-blue-500/40"
                    >
                        <AppLogoIcon
                            class="size-6 transition-transform duration-300 group-hover:scale-110"
                        />
                    </div>
                    <span
                        class="text-xl font-bold tracking-tight text-foreground"
                        >Finova</span
                    >
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        v-if="isAuthTab"
                        type="button"
                        title="Replay Splash & Onboarding Intro"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-border/80 bg-muted/50 px-3 py-1 text-xs font-medium text-muted-foreground transition-colors hover:border-blue-500/30 hover:bg-blue-500/10 hover:text-blue-600 active:scale-95 dark:hover:text-blue-400"
                        @click="replayIntro"
                    >
                        <Sparkles class="size-3.5" />
                        <span>Intro</span>
                    </button>

                    <div
                        class="hidden items-center gap-1.5 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-600 sm:inline-flex dark:text-blue-400"
                    >
                        <span class="relative flex size-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"
                            />
                            <span
                                class="relative inline-flex size-2 rounded-full bg-blue-500"
                            />
                        </span>
                        <span>Wealth OS</span>
                    </div>
                </div>
            </div>

            <!-- CENTER FORM CONTAINER -->
            <div
                class="mx-auto my-auto flex w-full max-w-[420px] flex-col justify-center py-8 sm:py-10"
            >
                <!-- TITLE & SUBTITLE -->
                <div class="mb-6 space-y-2 text-center sm:text-left">
                    <h1
                        class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        {{ displayTitle }}
                    </h1>
                    <p
                        v-if="displayDescription"
                        class="text-sm text-muted-foreground"
                    >
                        {{ displayDescription }}
                    </p>
                </div>

                <!-- SEGMENTED TAB SWITCHER (Sign In / Signup) -->
                <div
                    v-if="isAuthTab"
                    class="mb-6 flex rounded-2xl border border-slate-200/80 bg-slate-100 p-1.5 shadow-inner dark:border-slate-700/60 dark:bg-slate-800/80"
                >
                    <Link
                        :href="login()"
                        preserve-scroll
                        :class="[
                            'flex-1 rounded-xl py-2.5 text-center text-sm font-semibold transition-all duration-200 select-none',
                            isLogin
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-white dark:ring-white/10'
                                : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                        ]"
                    >
                        Sign In
                    </Link>
                    <Link
                        :href="register()"
                        preserve-scroll
                        :class="[
                            'flex-1 rounded-xl py-2.5 text-center text-sm font-semibold transition-all duration-200 select-none',
                            isRegister
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-white dark:ring-white/10'
                                : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                        ]"
                    >
                        Signup
                    </Link>
                </div>

                <!-- FORM BODY -->
                <slot />
            </div>

            <!-- BOTTOM FOOTER TESTIMONIAL / TRUST STATEMENT -->
            <div class="mx-auto w-full max-w-[420px] pt-4 text-center">
                <p class="text-xs leading-relaxed text-muted-foreground/80">
                    Join the millions of smart investors who trust us to manage
                    their finances. Log in to access your personalized
                    dashboard, track your portfolio performance, and make
                    informed investment decisions.
                </p>
            </div>
        </div>

        <!-- RIGHT COLUMN: VISUAL 3D SHOWCASE (DESKTOP ONLY) -->
        <div
            class="relative hidden h-full flex-col items-center justify-center overflow-hidden border-l border-slate-200/70 bg-gradient-to-br from-sky-50/80 via-blue-100/50 to-indigo-50/40 p-12 lg:flex dark:border-slate-800/60 dark:from-[#090f1d] dark:via-[#0e1933] dark:to-[#070b14]"
        >
            <!-- Ambient vertical luminous streaks / lines -->
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden select-none"
            >
                <!-- Line 1 -->
                <div
                    class="absolute top-12 left-1/6 h-36 w-[2px] rounded-full bg-gradient-to-b from-transparent via-blue-400/30 to-transparent blur-[1px] dark:via-blue-400/15"
                />
                <!-- Line 2 -->
                <div
                    class="absolute top-1/4 right-1/4 h-48 w-[2px] rounded-full bg-gradient-to-b from-transparent via-blue-400/40 to-transparent blur-[1px] dark:via-blue-400/20"
                />
                <!-- Line 3 -->
                <div
                    class="absolute bottom-20 left-1/3 h-40 w-[2px] rounded-full bg-gradient-to-b from-transparent via-indigo-400/30 to-transparent blur-[1px] dark:via-indigo-400/15"
                />
                <!-- Line 4 -->
                <div
                    class="absolute top-1/3 left-1/12 h-28 w-[2px] rounded-full bg-gradient-to-b from-transparent via-blue-300/30 to-transparent blur-[1px] dark:via-blue-300/10"
                />
                <!-- Line 5 -->
                <div
                    class="absolute right-1/10 bottom-1/3 h-32 w-[2px] rounded-full bg-gradient-to-b from-transparent via-blue-400/30 to-transparent blur-[1px] dark:via-blue-400/15"
                />

                <!-- Ambient Radial Glow Behind Vault -->
                <div
                    class="absolute top-1/2 left-1/2 h-[420px] w-[420px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-400/20 blur-[100px] dark:bg-blue-600/15"
                />
            </div>

            <!-- Centerpiece 3D Vault Illustration & Floating Card -->
            <div class="relative z-10 flex flex-col items-center">
                <!-- 3D Safe Vault with gentle float animation -->
                <div
                    class="group relative transition-transform duration-500 hover:scale-105"
                >
                    <img
                        src="/images/auth-vault.jpg"
                        alt="Finova Bank-Grade Safe Vault"
                        class="animate-float pointer-events-none h-[340px] w-[340px] rounded-3xl object-contain drop-shadow-[0_24px_48px_rgba(37,99,235,0.22)] select-none xl:h-[420px] xl:w-[420px]"
                    />
                </div>

                <!-- Floating Glass Trust Badge -->
                <div
                    class="mt-8 flex items-center gap-2.5 rounded-full border border-white/60 bg-white/70 px-5 py-2 text-xs font-medium text-slate-700 shadow-xl backdrop-blur-md dark:border-white/10 dark:bg-slate-900/70 dark:text-slate-300"
                >
                    <ShieldCheck
                        class="size-4 text-blue-600 dark:text-blue-400"
                    />
                    <span>Bank-Grade 256-Bit Security • 100% Privacy</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-8px);
    }
}

.animate-float {
    animation: float 5s ease-in-out infinite;
}
</style>
