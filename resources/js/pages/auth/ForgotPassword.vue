<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot Password',
        description:
            'Enter your registered email address to receive password reset instructions',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div
        v-if="status"
        class="mb-4 rounded-xl border border-green-500/20 bg-green-500/10 p-3 text-center text-sm font-medium text-green-500"
    >
        {{ status }}
    </div>

    <Form
        v-bind="email.form()"
        v-slot="{ errors, processing }"
        class="space-y-5"
    >
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
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="name@company.com"
                    class="h-11 rounded-2xl border-border/80 bg-input/40 pl-10 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <Button
            type="submit"
            class="flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-blue-600 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:bg-blue-500 hover:shadow-blue-500/40 active:scale-[0.99]"
            :disabled="processing"
            data-test="email-password-reset-link-button"
        >
            <Spinner v-if="processing" class="size-4" />
            <span>Email password reset link</span>
        </Button>

        <div class="pt-2 text-center text-xs text-muted-foreground sm:text-sm">
            <span>Remember your password?</span>
            <TextLink
                :href="login()"
                class="ml-1 font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
            >
                Return to sign in
            </TextLink>
        </div>
    </Form>
</template>
