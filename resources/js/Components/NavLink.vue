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
        ? 'inline-flex items-center border-b-2 border-brand-700 px-1 pt-1 text-sm font-semibold text-brand-900'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-semibold text-brand-700 transition hover:border-brand-100 hover:text-brand-900',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
