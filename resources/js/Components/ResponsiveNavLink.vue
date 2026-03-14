<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full border-r-4 border-brand-700 bg-brand-bg px-4 py-2 text-right text-base font-semibold text-brand-900'
        : 'block w-full border-r-4 border-transparent px-4 py-2 text-right text-base font-semibold text-brand-700 hover:bg-brand-bg',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
