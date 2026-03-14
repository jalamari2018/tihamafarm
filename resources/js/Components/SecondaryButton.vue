<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-brand-100 bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-bg focus:outline-none focus:ring-2 focus:ring-emerald-300"
    >
        <slot />
    </button>
</template>
