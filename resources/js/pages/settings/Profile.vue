<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
/* @chisel-email-verification */
import { Link } from '@inertiajs/vue3';
/* @end-chisel-email-verification */
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
/* @chisel-email-verification */
import { send } from '@/routes/verification';
/* @end-chisel-email-verification */

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
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your name and email address"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <!-- Financial Settings -->
            <div class="grid gap-2">
                <Label for="default_currency">Default Currency</Label>
                <select
                    id="default_currency"
                    name="default_currency"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 mt-1 block"
                    required
                >
                    <option value="IDR" :selected="user.default_currency === 'IDR'">IDR - Indonesian Rupiah</option>
                    <option value="USD" :selected="user.default_currency === 'USD'">USD - US Dollar</option>
                    <option value="EUR" :selected="user.default_currency === 'EUR'">EUR - Euro</option>
                    <option value="SGD" :selected="user.default_currency === 'SGD'">SGD - Singapore Dollar</option>
                </select>
                <InputError class="mt-2" :message="errors.default_currency" />
            </div>

            <div class="grid gap-2">
                <Label for="timezone">Timezone</Label>
                <select
                    id="timezone"
                    name="timezone"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 mt-1 block"
                    required
                >
                    <option value="Asia/Jakarta" :selected="user.timezone === 'Asia/Jakarta'">Asia/Jakarta (WIB)</option>
                    <option value="Asia/Makassar" :selected="user.timezone === 'Asia/Makassar'">Asia/Makassar (WITA)</option>
                    <option value="Asia/Jayapura" :selected="user.timezone === 'Asia/Jayapura'">Asia/Jayapura (WIT)</option>
                    <option value="UTC" :selected="user.timezone === 'UTC'">UTC</option>
                </select>
                <InputError class="mt-2" :message="errors.timezone" />
            </div>

            <div class="grid gap-2">
                <Label for="date_format">Date Format</Label>
                <select
                    id="date_format"
                    name="date_format"
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 mt-1 block"
                    required
                >
                    <option value="d/m/Y" :selected="user.date_format === 'd/m/Y'">DD/MM/YYYY (31/12/2026)</option>
                    <option value="Y-m-d" :selected="user.date_format === 'Y-m-d'">YYYY-MM-DD (2026-12-31)</option>
                    <option value="m/d/Y" :selected="user.date_format === 'm/d/Y'">MM/DD/YYYY (12/31/2026)</option>
                </select>
                <InputError class="mt-2" :message="errors.date_format" />
            </div>

            <div class="grid gap-2">
                <Label for="month_start_day">Month Start Day (e.g. for Salary)</Label>
                <Input
                    id="month_start_day"
                    type="number"
                    min="1"
                    max="28"
                    class="mt-1 block w-full"
                    name="month_start_day"
                    :default-value="user.month_start_day"
                    required
                    placeholder="25"
                />
                <InputError class="mt-2" :message="errors.month_start_day" />
            </div>

            <!-- @chisel-email-verification -->
            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>
            <!-- @end-chisel-email-verification -->

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
