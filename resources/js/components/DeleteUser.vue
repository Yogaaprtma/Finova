<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Loader2 } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <section
        class="rounded-xl border border-destructive/30 bg-card text-card-foreground"
    >
        <div
            class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:p-6"
        >
            <div class="min-w-0">
                <h3 class="text-base font-semibold tracking-tight">
                    Delete account
                </h3>
                <p class="mt-1 text-sm text-muted-foreground sm:max-w-md">
                    Permanently delete your account and all financial records,
                    including transactions, accounts, and budgets. This can’t
                    be undone.
                </p>
            </div>

            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        class="h-11 w-full shrink-0 text-sm font-medium sm:h-10 sm:w-auto"
                        data-test="delete-user-button"
                    >
                        Delete account
                    </Button>
                </DialogTrigger>

                <DialogContent class="sm:max-w-md">
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-5"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-2">
                            <DialogTitle class="text-lg font-semibold">
                                Delete your account?
                            </DialogTitle>
                            <DialogDescription
                                class="text-sm text-muted-foreground"
                            >
                                All of your financial records and data will be
                                permanently deleted. Enter your password to
                                confirm.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-2">
                            <Label for="password" class="text-sm font-medium">
                                Password
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                class="h-11 text-base md:h-10 md:text-sm"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter
                            class="flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                        >
                            <DialogClose as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-11 w-full text-sm sm:h-10 sm:w-auto"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                class="h-11 w-full gap-2 text-sm sm:h-10 sm:w-auto"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                <Loader2
                                    v-if="processing"
                                    class="size-4 animate-spin"
                                />
                                Delete account
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </section>
</template>