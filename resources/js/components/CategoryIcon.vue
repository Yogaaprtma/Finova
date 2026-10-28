<script setup lang="ts">
import {
    Banknote,
    Gift,
    TrendingUp,
    PlusCircle,
    Utensils,
    Car,
    ShoppingBag,
    Zap,
    Tv,
    Heart,
    Book,
    Smile,
    MinusCircle,
    Tag,
    Circle,
    Home,
    Plane,
    Briefcase,
    GraduationCap,
    Smartphone,
    Gamepad2,
} from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    icon?: string | null;
    className?: string;
}>();

const LUCIDE_ICONS: Record<string, any> = {
    banknote: Banknote,
    gift: Gift,
    'trending-up': TrendingUp,
    'plus-circle': PlusCircle,
    utensils: Utensils,
    car: Car,
    'shopping-bag': ShoppingBag,
    zap: Zap,
    tv: Tv,
    heart: Heart,
    book: Book,
    smile: Smile,
    'minus-circle': MinusCircle,
    tag: Tag,
    circle: Circle,
    home: Home,
    plane: Plane,
    briefcase: Briefcase,
    'graduation-cap': GraduationCap,
    smartphone: Smartphone,
    'gamepad-2': Gamepad2,
};

const isLucide = computed(() => {
    return props.icon && Boolean(LUCIDE_ICONS[props.icon.toLowerCase()]);
});

const lucideComponent = computed(() => {
    return props.icon ? LUCIDE_ICONS[props.icon.toLowerCase()] : Tag;
});
</script>

<template>
    <component
        :is="lucideComponent"
        v-if="isLucide"
        :class="className || 'h-5 w-5'"
    />
    <span v-else-if="icon" class="text-xl leading-none">{{ icon }}</span>
    <Tag v-else :class="className || 'h-5 w-5'" />
</template>
