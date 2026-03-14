<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timer = null;

const flash = computed(() => page.props.flash ?? {});

watch(
    flash,
    (value) => {
        const nextMessage = value.success ?? value.error;

        if (!nextMessage) {
            return;
        }

        message.value = nextMessage;
        type.value = value.error ? 'error' : 'success';
        visible.value = true;

        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            visible.value = false;
        }, 3000);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300"
        enter-from-class="translate-y-3 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-300"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-3 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed left-4 top-4 z-50 rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-lg"
            :class="type === 'error' ? 'bg-red-600' : 'bg-emerald-600'"
        >
            {{ message }}
        </div>
    </Transition>
</template>
