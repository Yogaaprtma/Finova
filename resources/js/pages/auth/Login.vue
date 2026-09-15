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
                <Label for="email" class="text-xs sm:text-sm font-medium text-foreground">Email address</Label>
                <div class="relative">
                    <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="pl-10 h-10.5 rounded-xl border-border/80 bg-input/40 dark:bg-[#131622]/80 focus-visible:ring-primary/25 transition-all text-sm placeholder:text-muted-foreground/60"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-xs sm:text-sm font-medium text-foreground">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-10.5 rounded-xl border-border/80 bg-input/40 dark:bg-[#131622]/80 focus-visible:ring-primary/25 transition-all text-sm placeholder:text-muted-foreground/60"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between text-sm mt-0.5">
                <Label for="remember" class="flex items-center gap-2.5 font-normal cursor-pointer select-none text-xs sm:text-sm text-muted-foreground hover:text-foreground transition-colors">
                    <Checkbox id="remember" name="remember" :tabindex="3" class="rounded-md border-border/80 data-[state=checked]:bg-primary data-[state=checked]:border-primary" />
                    <span>Remember me</span>
                </Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs sm:text-sm font-medium text-primary hover:text-primary-400 transition-colors"
                    :tabindex="5"
                >
                    Forgot password?
                </TextLink>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full h-11 rounded-xl bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-medium shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 active:scale-[0.99] transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer border-0"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="size-4" />
                <span>Sign In</span>
            </Button>
        </div>

        <div class="text-center text-xs sm:text-sm text-muted-foreground pt-1">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5" class="font-medium text-primary hover:text-primary-400 ml-1">Sign up</TextLink>
        </div>
    </Form>
</template>
