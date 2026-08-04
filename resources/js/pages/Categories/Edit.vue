<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import InputError from '@/components/InputError.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: '/categories',
            },
            {
                title: 'Edit',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    category: any;
    parentCategories: any[];
    categoryTypes: string[];
}>();

const form = useForm({
    name: props.category.name,
    type: props.category.type,
    parent_id: props.category.parent_id,
    icon: props.category.icon || '🏷️',
    color: props.category.color || '#3b82f6',
    is_active: props.category.is_active,
    sort_order: props.category.sort_order,
});

const submit = () => {
    form.put(`/categories/${props.category.id}`);
};

const EMOJI_OPTIONS = [
    '🏷️',
    '🛒',
    '🍽️',
    '🚗',
    '🏠',
    '🏥',
    '🎉',
    '✈️',
    '🎓',
    '💼',
    '💰',
    '📈',
    '📱',
    '👗',
    '🎮',
];
const COLOR_OPTIONS = [
    '#ef4444',
    '#f97316',
    '#f59e0b',
    '#84cc16',
    '#22c55e',
    '#10b981',
    '#14b8a6',
    '#06b6d4',
    '#0ea5e9',
    '#3b82f6',
    '#6366f1',
    '#8b5cf6',
    '#a855f7',
    '#d946ef',
    '#f43f5e',
];
</script>

<template>
    <Head title="Edit Category" />

    <div
        class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <div class="flex items-center gap-4">
            <Link
                href="/categories"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md border bg-background text-muted-foreground shadow-sm hover:bg-muted hover:text-foreground"
            >
                <ArrowLeft class="h-4 w-4" />
                <span class="sr-only">Back</span>
            </Link>
            <h1 class="text-2xl font-bold tracking-tight">Edit Category</h1>
        </div>

        <div class="rounded-xl border bg-card text-card-foreground shadow">
            <form @submit.prevent="submit" class="p-6">
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <label
                            for="name"
                            class="text-sm leading-none font-medium"
                            >Name</label
                        >
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <label
                            for="type"
                            class="text-sm leading-none font-medium"
                            >Type</label
                        >
                        <select
                            id="type"
                            v-model="form.type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm capitalize ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            required
                            :disabled="category.is_system"
                        >
                            <option
                                v-for="type in categoryTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                        <p
                            v-if="category.is_system"
                            class="text-xs text-muted-foreground"
                        >
                            System category types cannot be changed.
                        </p>
                    </div>

                    <div class="grid gap-2" v-if="parentCategories.length > 0">
                        <label
                            for="parent_id"
                            class="text-sm leading-none font-medium"
                            >Parent Category (Optional)</label
                        >
                        <select
                            id="parent_id"
                            v-model="form.parent_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option :value="null">
                                -- None (Top Level) --
                            </option>
                            <option
                                v-for="parent in parentCategories"
                                :key="parent.id"
                                :value="parent.id"
                            >
                                {{ parent.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.parent_id" />
                    </div>

                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="h-4 w-4 rounded border-input bg-background text-primary focus:ring-primary focus:ring-offset-background"
                        />
                        <label
                            for="is_active"
                            class="text-sm leading-none font-medium"
                        >
                            Active (Available for new transactions)
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm leading-none font-medium"
                                >Icon</label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    v-for="emoji in EMOJI_OPTIONS"
                                    :key="emoji"
                                    @click="form.icon = emoji"
                                    class="flex h-10 w-10 items-center justify-center rounded-md border text-lg transition-colors hover:bg-muted"
                                    :class="
                                        form.icon === emoji
                                            ? 'border-primary bg-primary/10 ring-1 ring-primary'
                                            : 'border-input bg-background'
                                    "
                                >
                                    {{ emoji }}
                                </button>
                            </div>
                            <InputError :message="form.errors.icon" />
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm leading-none font-medium"
                                >Color</label
                            >
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    v-for="color in COLOR_OPTIONS"
                                    :key="color"
                                    @click="form.color = color"
                                    class="flex h-8 w-8 items-center justify-center rounded-full transition-transform hover:scale-110"
                                    :class="
                                        form.color === color
                                            ? 'ring-2 ring-foreground ring-offset-2 ring-offset-background'
                                            : ''
                                    "
                                    :style="{ backgroundColor: color }"
                                    :aria-label="`Select color ${color}`"
                                ></button>
                            </div>
                            <InputError :message="form.errors.color" />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-8 flex items-center justify-end gap-4 border-t pt-6"
                >
                    <Link
                        href="/categories"
                        class="text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
