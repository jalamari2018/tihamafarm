<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-right text-sm font-semibold text-brand-700 transition hover:bg-brand-bg focus:bg-brand-bg focus:outline-none"
    >
        <slot />
    </Link>
</template>
