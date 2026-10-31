<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail, User } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an Account',
        description: 'Start managing your wealth and financial freedom today',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-3.5">
            <!-- Full Name Input -->
            <div class="grid gap-1.5">
                <Label
                    for="name"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    Full Name
                </Label>
                <div class="relative">
                    <User
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Your full name"
                        class="h-11 rounded-2xl border-border/80 bg-input/40 pl-10 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                    />
                </div>
                <InputError :message="errors.name" />
            </div>

            <!-- Email Address Input -->
            <div class="grid gap-1.5">
                <Label
                    for="email"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    Email Address
                </Label>
                <div class="relative">
                    <Mail
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="name@company.com"
                        class="h-11 rounded-2xl border-border/80 bg-input/40 pl-10 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password Input -->
            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    Password
                </Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Create a secure password"
                    :passwordrules="passwordRules"
                    class="h-11 rounded-2xl border-border/80 bg-input/40 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                />
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password Input -->
            <div class="grid gap-1.5">
                <Label
                    for="password_confirmation"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    Confirm Password
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Repeat your password"
                    :passwordrules="passwordRules"
                    class="h-11 rounded-2xl border-border/80 bg-input/40 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <!-- Submit Button (Create Account) -->
            <Button
                type="submit"
                class="mt-2 flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-blue-600 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:bg-blue-500 hover:shadow-blue-500/40 active:scale-[0.99]"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="size-4" />
                <span>Create Account</span>
            </Button>
        </div>

        <!-- Or Continue With Divider -->
        <div class="relative my-1">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-border/70" />
            </div>
            <div class="relative flex justify-center text-xs">
                <span
                    class="bg-background px-3 font-medium text-muted-foreground"
                >
                    Or Continue With
                </span>
            </div>
        </div>

        <!-- Social / Quick Sign-In Circles (Google, Apple, Facebook) -->
        <div class="flex items-center justify-center gap-4">
            <!-- Google -->
            <a
                href="/auth/google/redirect"
                class="flex size-11 items-center justify-center rounded-full border border-border bg-background shadow-sm transition-all duration-200 hover:scale-105 hover:bg-muted/50 active:scale-95"
                aria-label="Continue with Google"
                title="Continue with Google"
            >
                <svg class="size-5" viewBox="0 0 24 24">
                    <path
                        fill="#4285F4"
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    />
                    <path
                        fill="#34A853"
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    />
                    <path
                        fill="#FBBC05"
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                    />
                    <path
                        fill="#EA4335"
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                    />
                </svg>
            </a>

            <!-- Apple -->
            <a
                href="/auth/apple/redirect"
                class="flex size-11 items-center justify-center rounded-full bg-slate-900 text-white shadow-sm transition-all duration-200 hover:scale-105 hover:bg-black active:scale-95 dark:bg-white dark:text-black"
                aria-label="Continue with Apple"
                title="Continue with Apple"
            >
                <svg class="size-5 fill-current" viewBox="0 0 24 24">
                    <path
                        d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.63-.78 1.06-1.85.94-2.93-.93.04-2.04.62-2.7 1.39-.58.67-1.09 1.76-.96 2.81 1.04.08 2.09-.53 2.72-1.27z"
                    />
                </svg>
            </a>

            <!-- Facebook -->
            <a
                href="/auth/facebook/redirect"
                class="flex size-11 items-center justify-center rounded-full bg-[#1877F2] text-white shadow-sm transition-all duration-200 hover:scale-105 hover:bg-[#166fe5] active:scale-95"
                aria-label="Continue with Facebook"
                title="Continue with Facebook"
            >
                <svg class="size-5 fill-current" viewBox="0 0 24 24">
                    <path
                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                    />
                </svg>
            </a>
        </div>

        <!-- Bottom Switch Helper -->
        <div class="pt-2 text-center text-xs text-muted-foreground sm:text-sm">
            Already have an account?
            <TextLink
                :href="login()"
                :tabindex="6"
                class="ml-1 font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
            >
                Sign in
            </TextLink>
        </div>
    </Form>
</template>
