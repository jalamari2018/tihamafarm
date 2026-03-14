<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import FlashToast from '@/Components/FlashToast.vue';
import Modal from '@/Components/Modal.vue';
import { Link, router } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
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

        <nav class="border-b border-brand-100 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center gap-6">
                        <Link :href="route('home')" class="h-12">
                            <ApplicationLogo />
                        </Link>

                        <div class="hidden items-center gap-4 sm:flex">
                            <NavLink :href="route('home')" :active="route().current('home')">
                                الرئيسية
                            </NavLink>
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                لوحة التحكم
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden items-center sm:flex">
                        <Dropdown align="left" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium text-brand-700 transition hover:text-brand-900 focus:outline-none"
                                    >
                                        {{ $page.props.auth.user.name }}
                                        <svg class="me-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">الملف الشخصي</DropdownLink>
                                <button type="button" class="block w-full px-4 py-2 text-right text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none" @click="submitLogout">
                                    تسجيل الخروج
                                </button>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="flex items-center sm:hidden">
                        <button
                            class="rounded-md p-2 text-brand-700"
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="border-t border-brand-100 sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <template v-if="$page.props.auth.user.role === 'admin'">
                        <ResponsiveNavLink :href="route('dashboard', { panel: 'stats' })" :active="$page.url.includes('panel=stats') || (route().current('dashboard') && !$page.url.includes('panel='))">
                            الإحصائيات
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('dashboard', { panel: 'farms' })" :active="$page.url.includes('panel=farms')">
                            المزارع
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('dashboard', { panel: 'harvests' })" :active="$page.url.includes('panel=harvests')">
                            المحاصيل
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('dashboard', { panel: 'equipment' })" :active="$page.url.includes('panel=equipment')">
                            المعدات
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('dashboard', { panel: 'users' })" :active="$page.url.includes('panel=users')">
                            المستخدمون
                        </ResponsiveNavLink>
                    </template>
                    <template v-else>
                        <ResponsiveNavLink :href="route('home')" :active="route().current('home')">
                            الرئيسية
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            لوحة التحكم
                        </ResponsiveNavLink>
                    </template>
                    <ResponsiveNavLink :href="route('profile.edit')">الملف الشخصي</ResponsiveNavLink>
                    <button type="button" class="block w-full border-r-4 border-transparent px-4 py-2 text-right text-base font-semibold text-brand-700 hover:bg-brand-bg" @click="submitLogout">
                        تسجيل الخروج
                    </button>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot name="header" />
        </header>

        <main class="pb-10">
            <slot />
        </main>

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
