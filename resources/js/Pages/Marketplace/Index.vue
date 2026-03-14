<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashToast from '@/Components/FlashToast.vue';

defineProps({
    farms: {
        type: Array,
        default: () => [],
    },
    harvests: {
        type: Array,
        default: () => [],
    },
    equipment: {
        type: Array,
        default: () => [],
    },
});

const formatPrice = (value) => new Intl.NumberFormat('ar-SA').format(value);
</script>

<template>
    <Head title="ربط أصحاب المزارع" />

    <div class="min-h-screen bg-brand-bg" dir="rtl">
        <FlashToast />

        <header class="border-b border-brand-100 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="h-16">
                    <ApplicationLogo />
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white"
                    >
                        لوحة التحكم
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-xl border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-700">
                            تسجيل الدخول
                        </Link>
                        <Link :href="route('register')" class="rounded-xl bg-brand-900 px-4 py-2 text-sm font-semibold text-white">
                            إنشاء حساب
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-3xl border border-brand-100 bg-white/90 p-6 shadow-lg">
                <h1 class="text-3xl font-extrabold text-brand-900">ربط أصحاب المزارع بالمستثمرين</h1>
                <p class="mt-2 text-brand-700">منصة واحدة لعرض المزارع والمحاصيل والمعدات الزراعية مع أرقام تواصل مباشرة.</p>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المزارع</h2>
                    <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ farms.length }} إعلان</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="farm in farms" :key="`farm-${farm.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                        <img :src="farm.image_url" alt="farm" class="h-48 w-full object-cover" />
                        <div class="space-y-2 p-4 text-sm">
                            <h3 class="text-lg font-bold text-brand-900">{{ farm.farm_name }}</h3>
                            <p><strong>المزارع:</strong> {{ farm.farmer_name }}</p>
                            <p><strong>الهاتف:</strong> {{ farm.phone }}</p>
                            <p><strong>الموقع:</strong> {{ farm.location_text }}</p>
                            <p><strong>المساحة:</strong> {{ farm.area }} م² ({{ farm.length }} × {{ farm.width }})</p>
                            <p v-if="farm.description" class="text-brand-700">{{ farm.description }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المحاصيل</h2>
                    <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ harvests.length }} إعلان</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="harvest in harvests" :key="`harvest-${harvest.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                        <img :src="harvest.image_url" alt="harvest" class="h-48 w-full object-cover" />
                        <div class="space-y-2 p-4 text-sm">
                            <h3 class="text-lg font-bold text-brand-900">{{ harvest.harvest_name }}</h3>
                            <p><strong>المزارع:</strong> {{ harvest.farmer_name }}</p>
                            <p><strong>الهاتف:</strong> {{ harvest.phone }}</p>
                            <p><strong>الموقع:</strong> {{ harvest.location_text }}</p>
                            <p v-if="harvest.ready_status === 'now'" class="font-semibold text-emerald-700">المحصول جاهز الآن</p>
                            <p v-else class="font-semibold text-amber-700">
                                سيكون جاهزًا خلال {{ harvest.ready_in_days }} يوم
                            </p>
                            <p v-if="harvest.description" class="text-brand-700">{{ harvest.description }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المعدات الزراعية</h2>
                    <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ equipment.length }} إعلان</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="item in equipment" :key="`equipment-${item.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                        <img :src="item.image_url" alt="equipment" class="h-48 w-full object-cover" />
                        <div class="space-y-2 p-4 text-sm">
                            <h3 class="text-lg font-bold text-brand-900">{{ item.product_name }}</h3>
                            <p><strong>البائع:</strong> {{ item.seller_name }}</p>
                            <p><strong>الهاتف:</strong> {{ item.phone }}</p>
                            <p><strong>الموقع:</strong> {{ item.location_text }}</p>
                            <p class="font-bold text-brand-900">السعر: {{ formatPrice(item.price) }}</p>
                            <p v-if="item.description" class="text-brand-700">{{ item.description }}</p>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>
