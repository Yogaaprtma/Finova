<script setup lang="ts">
import { Eye, EyeOff, Lock } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import type { HTMLAttributes } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        showLockIcon?: boolean;
    }>(),
    {
        showLockIcon: true,
    },
);

const showPassword = ref(false);
const inputRef = useTemplateRef('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.$el?.focus(),
});
</script>

<template>
    <div class="relative">
        <Lock
            v-if="showLockIcon"
            class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
        />
        <Input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            :class="cn(showLockIcon ? 'pl-10' : '', 'pr-10', props.class)"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3.5 text-muted-foreground hover:text-foreground focus-visible:outline-none transition-colors cursor-pointer"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            :tabindex="-1"
        >
            <EyeOff v-if="showPassword" class="size-4" />
            <Eye v-else class="size-4" />
        </button>
    </div>
</template>
