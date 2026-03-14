<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashToast from '@/Components/FlashToast.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-brand-bg py-8" dir="rtl">
        <FlashToast />

        <div class="mx-auto max-w-md px-4">
            <div class="mb-6 flex justify-center">
                <Link href="/" class="h-20">
                    <ApplicationLogo />
                </Link>
            </div>

            <div class="rounded-2xl border border-brand-100 bg-white/95 p-6 shadow-xl backdrop-blur">
                <slot />
            </div>
        </div>
    </div>
</template>
