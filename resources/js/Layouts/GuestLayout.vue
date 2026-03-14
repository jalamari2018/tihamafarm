<script setup>
import FlashToast from '@/Components/FlashToast.vue';
import Modal from '@/Components/Modal.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const showLogoutModal = ref(false);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const submitLogout = async () => {
    showLogoutModal.value = true;
    await sleep(1200);

    router.post(route('logout'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.href = route('home');
        },
        onFinish: () => {
            showLogoutModal.value = false;
        },
    });
};
</script>

<template>
    <div class="min-h-screen bg-brand-bg" dir="rtl">
        <FlashToast />

        <header class="border-b border-brand-100 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white p-1">
                    <img src="/assets/logo.jpeg" alt="Tihama Farm" class="h-full w-full rounded-full object-cover" />
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('login')"
                        class="rounded-xl border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-700"
                    >
                        تسجيل الدخول
                    </Link>
                    <Link
                        v-else
                        :href="route('dashboard', { panel: 'myads' })"
                        class="inline-flex items-center gap-2 rounded-xl border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-bg"
                    >
                        <span v-if="page.props.auth.user.avatar_url" class="inline-flex h-7 w-7 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white">
                            <img :src="page.props.auth.user.avatar_url" alt="avatar" class="h-full w-full object-cover" />
                        </span>
                        <span v-else class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-bg text-brand-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                        {{ page.props.auth.user.name }}
                    </Link>
                    <button
                        v-if="page.props.auth.user"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-brand-100 text-brand-700 transition hover:bg-brand-bg"
                        aria-label="تسجيل الخروج"
                        @click="submitLogout"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 17l5-5-5-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12H9" />
                        </svg>
                    </button>
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('register')"
                        class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-extrabold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500"
                    >
                        اعلن مجانا
                    </Link>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-md px-4 py-8">
            <div class="mb-6 flex justify-center">
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-full border border-brand-100 bg-white text-brand-700 shadow-sm">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                </div>
            </div>
            <div class="rounded-2xl border border-brand-100 bg-white/95 p-6 shadow-xl backdrop-blur">
                <slot />
            </div>
        </div>

        <Modal :show="showLogoutModal" max-width="md" :closeable="false">
            <div class="p-6" dir="rtl">
                <h3 class="text-lg font-bold text-brand-900">جاري تسجيل الخروج</h3>
                <p class="mt-2 text-sm text-brand-700">يرجى الانتظار...</p>
                <div class="mt-5 flex justify-center">
                    <span class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></span>
                </div>
            </div>
        </Modal>
    </div>
</template>
