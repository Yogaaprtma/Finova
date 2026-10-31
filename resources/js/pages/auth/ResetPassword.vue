<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset Password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Reset password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="space-y-4"
    >
        <div class="grid gap-3.5">
            <!-- Readonly Email -->
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
                        autocomplete="email"
                        v-model="inputEmail"
                        readonly
                        class="h-11 rounded-2xl border-border/80 bg-muted/40 pl-10 text-sm opacity-80"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- New Password -->
            <div class="grid gap-1.5">
                <Label
                    for="password"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    New Password
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    placeholder="Enter new password"
                    :passwordrules="passwordRules"
                    class="h-11 rounded-2xl border-border/80 bg-input/40 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                />
                <InputError :message="errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="grid gap-1.5">
                <Label
                    for="password_confirmation"
                    class="text-xs font-medium text-foreground sm:text-sm"
                >
                    Confirm Password
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm new password"
                    :passwordrules="passwordRules"
                    class="h-11 rounded-2xl border-border/80 bg-input/40 text-sm transition-all placeholder:text-muted-foreground/60 focus-visible:ring-2 focus-visible:ring-blue-500/25 dark:bg-[#131622]/80"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 flex h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-0 bg-blue-600 font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-200 hover:bg-blue-500 hover:shadow-blue-500/40 active:scale-[0.99]"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" class="size-4" />
                <span>Reset password</span>
            </Button>
        </div>
    </Form>
</template>
