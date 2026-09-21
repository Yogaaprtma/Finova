<script setup lang="ts">
import { Mail } from '@lucide/vue';
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Sign in to access your financial dashboard',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-green-500/20 bg-green-500/10 p-3 text-center text-sm font-medium text-green-500"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label
                    for="email"
                    class="text-xs font-medium text-foreground sm:text-sm"
                    >Email address</Label
                >
                <div class="relative">
                    <Mail
                        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="h-10.5 rounded-xl border-border/80 bg-input/40 pl-10 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-primary/25 dark:bg-[#131622]/80"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label
                    for="password"
                    class="text-xs font-medium text-foreground sm:text-sm"
                    >Password</Label
                >
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-10.5 rounded-xl border-border/80 bg-input/40 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-primary/25 dark:bg-[#131622]/80"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="mt-0.5 flex items-center justify-between text-sm">
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center gap-2.5 text-xs font-normal text-muted-foreground transition-colors select-none hover:text-foreground sm:text-sm"
                >
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="rounded-md border-border/80 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                    />
                    <span>Remember me</span>
                </Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="hover:text-primary-400 text-xs font-medium text-primary transition-colors sm:text-sm"
                    :tabindex="5"
                >
                    Forgot password?
                </TextLink>
            </div>

            <Button
                type="submit"
                class="mt-2 flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border-0 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 font-medium text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:from-blue-500 hover:to-indigo-500 hover:shadow-blue-500/40 active:scale-[0.99]"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="size-4" />
                <span>Sign In</span>
            </Button>
        </div>

        <div class="pt-1 text-center text-xs text-muted-foreground sm:text-sm">
            Don't have an account?
            <TextLink
                :href="register()"
                :tabindex="5"
                class="hover:text-primary-400 ml-1 font-medium text-primary"
                >Sign up</TextLink
            >
        </div>
    </Form>
</template>
