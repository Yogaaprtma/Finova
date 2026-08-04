<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusCircle,
    Pencil,
    Trash2,
    Tags,
    ArrowUp,
    ArrowDown,
    CornerDownRight,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import EmptyState from '@/components/EmptyState.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: '/categories',
            },
        ],
    },
});

const props = defineProps<{
    categories: any[];
}>();

const deleteTarget = ref<any | null>(null);

const confirmDelete = (category: any) => {
    deleteTarget.value = category;
};

const executeDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(`/categories/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const groupedCategories = computed(() => {
    const groups: Record<string, any[]> = {
        expense: [],
        income: [],
        transfer: [],
    };

    props.categories.forEach((category) => {
        if (groups[category.type]) {
            groups[category.type].push(category);
        }
    });

    return groups;
});

const moveCategory = (category: any, direction: 'up' | 'down') => {
    const list = [...(groupedCategories.value[category.type] || [])];
    const currentIndex = list.findIndex((c) => c.id === category.id);

    if (currentIndex === -1) {
        return;
    }

    const targetIndex =
        direction === 'up' ? currentIndex - 1 : currentIndex + 1;

    if (targetIndex < 0 || targetIndex >= list.length) {
        return;
    }

    // Swap sort orders
    const currentItem = list[currentIndex];
    const targetItem = list[targetIndex];

    const currentOrder = currentItem.sort_order ?? currentIndex;
    const targetOrder = targetItem.sort_order ?? targetIndex;

    router.post(
        '/categories/reorder',
        {
            categories: [
                { id: currentItem.id, sort_order: targetOrder },
                { id: targetItem.id, sort_order: currentOrder },
            ],
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Categories" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Categories</h1>
                <p class="mt-1 text-muted-foreground">
                    Manage your financial categories and subcategories.
                </p>
            </div>
            <Link
                href="/categories/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Category
            </Link>
        </div>

        <!-- Categories Grid -->
        <div v-if="categories.length > 0" class="flex flex-col gap-8">
            <template v-for="(cats, type) in groupedCategories" :key="type">
                <div v-if="cats.length > 0">
                    <h2
                        class="mb-4 flex items-center gap-2 text-xl font-semibold capitalize"
                        :class="{
                            'text-rose-500': type === 'expense',
                            'text-emerald-500': type === 'income',
                            'text-blue-500': type === 'transfer',
                        }"
                    >
                        {{ type }}
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                            >{{ cats.length }}</span
                        >
                    </h2>
                    <div
                        class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3"
                    >
                        <div
                            v-for="(category, idx) in cats"
                            :key="category.id"
                            class="group relative flex flex-col justify-between rounded-xl border bg-card p-4 shadow-sm transition-all hover:border-primary/50"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div
                                    class="flex items-center gap-3 overflow-hidden"
                                >
                                    <div
                                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full"
                                        :style="{
                                            backgroundColor: category.color
                                                ? `${category.color}20`
                                                : 'var(--muted)',
                                            color:
                                                category.color ||
                                                'var(--foreground)',
                                        }"
                                    >
                                        <CategoryIcon
                                            :icon="category.icon"
                                            class-name="h-5 w-5"
                                        />
                                    </div>
                                    <div class="truncate">
                                        <h3
                                            class="truncate leading-none font-semibold text-foreground"
                                        >
                                            {{ category.name }}
                                        </h3>
                                        <p
                                            v-if="category.is_system"
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            System Default
                                        </p>
                                        <p
                                            v-else-if="!category.is_active"
                                            class="mt-1 text-xs text-muted-foreground line-through"
                                        >
                                            Inactive
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="flex items-center gap-1 rounded-md border bg-card p-0.5 opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                                >
                                    <button
                                        type="button"
                                        :disabled="idx === 0"
                                        @click="moveCategory(category, 'up')"
                                        class="p-1.5 text-muted-foreground transition-colors hover:text-foreground disabled:opacity-30"
                                        title="Move Up"
                                    >
                                        <ArrowUp class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="idx === cats.length - 1"
                                        @click="moveCategory(category, 'down')"
                                        class="p-1.5 text-muted-foreground transition-colors hover:text-foreground disabled:opacity-30"
                                        title="Move Down"
                                    >
                                        <ArrowDown class="h-3.5 w-3.5" />
                                    </button>
                                    <Link
                                        :href="`/categories/${category.id}/edit`"
                                        class="p-1.5 text-muted-foreground hover:text-foreground"
                                        title="Edit"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button
                                        v-if="!category.is_system"
                                        type="button"
                                        @click="confirmDelete(category)"
                                        class="p-1.5 text-muted-foreground hover:text-destructive"
                                        title="Delete"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Subcategories Display (under parent) -->
                            <div
                                v-if="
                                    category.children &&
                                    category.children.length > 0
                                "
                                class="mt-4 border-t pt-3"
                            >
                                <p
                                    class="mb-2 text-xs font-medium text-muted-foreground"
                                >
                                    Subcategories ({{
                                        category.children.length
                                    }}):
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <div
                                        v-for="sub in category.children"
                                        :key="sub.id"
                                        class="inline-flex items-center gap-1 rounded-md bg-muted/60 px-2 py-1 text-xs text-foreground"
                                    >
                                        <CornerDownRight
                                            class="h-3 w-3 text-muted-foreground"
                                        />
                                        <span>{{ sub.name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :icon="Tags"
            title="No categories found"
            description="You haven't created any custom categories yet. Categories help organize your income and expense tracking."
            action-text="Add Your First Category"
            action-href="/categories/create"
        />

        <!-- Confirmation Delete Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-xl border bg-card p-6 text-card-foreground shadow-lg"
            >
                <h3 class="text-lg font-semibold">Delete Category</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-bold text-foreground"
                        >"{{ deleteTarget.name }}"</span
                    >? Transactions associated with this category will remain,
                    but will become uncategorized.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="deleteTarget = null"
                        class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
