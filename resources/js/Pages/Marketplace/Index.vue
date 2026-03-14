<script setup>
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import FlashToast from '@/Components/FlashToast.vue';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
const formatArabicNumber = (value) => new Intl.NumberFormat('ar-SA').format(Number(value) || 0);
const formatAdsCount = (count) => `${formatArabicNumber(count)} ${count > 10 ? 'اعلان' : 'اعلانات'}`;
const formatReadyDays = (days) => {
    const value = Number(days) || 0;

    if (value === 1) return `${formatArabicNumber(value)} يوم`;
    if (value === 2) return 'يومين';
    if (value <= 10) return `${formatArabicNumber(value)} ايام`;
    return `${formatArabicNumber(value)} أيام`;
};
const activeFilter = ref(null);
const showAuthPromptModal = ref(false);
const showLogoutModal = ref(false);
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const heroTitleFull = 'بوابة موحدة لإعلانات المزارع والمحاصيل والمعدات في تهامة عسير';
const heroSubtitleFull = 'مشروع أجاويد 4 لدعم المزارعين وربط فرصهم بالمستثمرين بطريقة مباشرة وواضحة.';
const heroTitleTyped = ref('');
const heroSubtitleTyped = ref('');
const isHeroTyping = ref(false);
const showHeroFilters = ref(false);
const showScrollTopButton = ref(false);
const scrollTopButtonBottom = ref(24);
let typingTimer = null;
let filtersRevealTimer = null;

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

const scrollToListings = () => {
    activeFilter.value = 'farms';
    const farmsSection = document.getElementById('farms-section');
    if (!farmsSection) return;
    const navbarOffset = 96;
    const top = farmsSection.getBoundingClientRect().top + window.scrollY - navbarOffset;
    window.scrollTo({ top, behavior: 'smooth' });
};

const runHeroTyping = () => {
    if (typingTimer) clearInterval(typingTimer);
    if (filtersRevealTimer) clearTimeout(filtersRevealTimer);
    heroTitleTyped.value = '';
    heroSubtitleTyped.value = '';
    showHeroFilters.value = false;
    isHeroTyping.value = true;
    let titleIndex = 0;
    let subtitleIndex = 0;
    let phase = 'title';

    typingTimer = setInterval(() => {
        if (phase === 'title') {
            heroTitleTyped.value = heroTitleFull.slice(0, titleIndex + 1);
            titleIndex += 1;
            if (titleIndex >= heroTitleFull.length) {
                phase = 'subtitle';
            }
            return;
        }

        heroSubtitleTyped.value = heroSubtitleFull.slice(0, subtitleIndex + 2);
        subtitleIndex += 2;
        if (subtitleIndex >= heroSubtitleFull.length) {
            clearInterval(typingTimer);
            typingTimer = null;
            isHeroTyping.value = false;
            filtersRevealTimer = setTimeout(() => {
                showHeroFilters.value = true;
                filtersRevealTimer = null;
            }, 120);
        }
    }, 85);
};

const handleFilterClick = async (category) => {
    activeFilter.value = category;
    await nextTick();
    const section = document.getElementById(`${category}-section`);
    if (!section) return;
    const navbarOffset = 96;
    const top = section.getBoundingClientRect().top + window.scrollY - navbarOffset;
    window.scrollTo({ top, behavior: 'smooth' });
};

const updateScrollTopButton = () => {
    const footer = document.getElementById('site-footer');
    if (!footer) {
        showScrollTopButton.value = false;
        scrollTopButtonBottom.value = 72;
        return;
    }
    const rect = footer.getBoundingClientRect();
    showScrollTopButton.value = rect.top <= window.innerHeight - 140;
    if (!showScrollTopButton.value) {
        scrollTopButtonBottom.value = 72;
        return;
    }
    const overlap = Math.max(0, window.innerHeight - rect.top);
    scrollTopButtonBottom.value = Math.min(72 + overlap, 138);
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    runHeroTyping();
    updateScrollTopButton();
    window.addEventListener('scroll', updateScrollTopButton, { passive: true });
    window.addEventListener('resize', updateScrollTopButton);
});

onBeforeUnmount(() => {
    if (typingTimer) clearInterval(typingTimer);
    if (filtersRevealTimer) clearTimeout(filtersRevealTimer);
    window.removeEventListener('scroll', updateScrollTopButton);
    window.removeEventListener('resize', updateScrollTopButton);
});
</script>

<template>
    <Head title="مزارع تهامة" />

    <div class="flex min-h-screen flex-col bg-brand-bg" dir="rtl">
        <FlashToast />

        <header class="fixed top-0 z-50 w-full border-b border-white/30 bg-white/35 shadow-sm backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2" style="direction:ltr;">
                    <div class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white">
                        <img src="/assets/ajaweedlogo.svg" alt="أجاويد 4" class="h-full w-full rounded-full object-cover" />
                    </div>
                    <div class="inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white">
                        <img src="/assets/logo.jpeg" alt="Tihama Farm" class="h-full w-full rounded-full object-cover" />
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="!$page.props.auth.user"
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
                        <span v-if="$page.props.auth.user.avatar_url" class="inline-flex h-7 w-7 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white">
                            <img :src="$page.props.auth.user.avatar_url" alt="avatar" class="h-full w-full object-cover" />
                        </span>
                        <span v-else class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-bg text-brand-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                        {{ $page.props.auth.user.name }}
                    </Link>
                    <button
                        v-if="$page.props.auth.user"
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
                    <template v-if="!$page.props.auth.user">
                        <button
                            class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-extrabold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500"
                            @click="showAuthPromptModal = true"
                        >
                            اعلن مجانا
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <main class="w-full flex-1 space-y-10">
            <section class="relative isolate min-h-screen overflow-hidden">
                <div class="absolute inset-0">
                    <iframe
                        class="pointer-events-none absolute left-1/2 top-1/2 h-[56.25vw] min-h-full w-[177.78vh] min-w-full -translate-x-1/2 -translate-y-1/2"
                        src="https://www.youtube.com/embed/85kTHwJ1Ju8?autoplay=1&mute=1&loop=1&playlist=85kTHwJ1Ju8&controls=0&modestbranding=1&rel=0&playsinline=1"
                        title="Video background"
                        frameborder="0"
                        allow="autoplay; encrypted-media"
                        allowfullscreen
                    ></iframe>
                </div>
                <div class="absolute inset-0 bg-emerald-950/55"></div>

                <div class="relative flex min-h-screen items-start justify-start p-4 pt-40 sm:justify-start sm:p-6 sm:pt-32">
                    <div class="w-full max-w-[22rem] bg-transparent p-4 text-white sm:max-w-3xl sm:p-7">
                        <h1 class="text-xl font-extrabold leading-tight sm:text-4xl">
                            {{ heroTitleTyped }}<span v-if="isHeroTyping && !heroSubtitleTyped.length" class="ml-1 inline-block animate-pulse">|</span>
                        </h1>
                        <p class="mt-3 min-h-[2.75rem] text-xs text-emerald-50 sm:min-h-[1.75rem] sm:text-base">
                            {{ heroSubtitleTyped }}<span v-if="isHeroTyping && heroSubtitleTyped.length" class="ml-1 inline-block animate-pulse">|</span>
                        </p>

                        <div
                            class="mt-5 flex flex-wrap gap-2 transition-all duration-700 ease-out"
                            :class="showHeroFilters ? 'translate-x-0 opacity-100' : '-translate-x-10 opacity-0'"
                        >
                            <button
                                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                                :class="activeFilter === 'farms' ? 'bg-white text-brand-900' : 'bg-white/20 text-white hover:bg-white/30'"
                                @click="handleFilterClick('farms')"
                            >
                                المزارع
                            </button>
                            <button
                                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                                :class="activeFilter === 'harvests' ? 'bg-white text-brand-900' : 'bg-white/20 text-white hover:bg-white/30'"
                                @click="handleFilterClick('harvests')"
                            >
                                المحاصيل
                            </button>
                            <button
                                class="rounded-xl px-4 py-2 text-sm font-bold transition"
                                :class="activeFilter === 'equipment' ? 'bg-white text-brand-900' : 'bg-white/20 text-white hover:bg-white/30'"
                                @click="handleFilterClick('equipment')"
                            >
                                المعدات
                            </button>
                        </div>
                    </div>

                    <div class="absolute inset-x-0 bottom-6 flex justify-center">
                        <button
                            type="button"
                            class="inline-flex h-14 w-14 animate-bounce items-center justify-center rounded-full bg-white/20 text-white shadow-lg backdrop-blur-sm transition hover:bg-white/30"
                            aria-label="النزول إلى الإعلانات"
                            @click="scrollToListings"
                        >
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            <div id="listings-start" class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8"></div>

            <div class="mx-auto w-full max-w-7xl space-y-10 px-4 pb-8 sm:px-6 lg:px-8">
            <section id="farms-section" class="scroll-mt-28 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المزارع</h2>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800 transition hover:bg-brand-200"
                            @click="scrollToTop"
                        >
                            للأعلى
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6" />
                            </svg>
                        </button>
                        <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ formatAdsCount(farms.length) }}</span>
                    </div>
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
                            <p><strong>البئر:</strong> {{ farm.has_well ? 'متوفر' : 'غير متوفر' }}</p>
                            <p><strong>الكهرباء:</strong> {{ farm.has_electricity ? 'متوفرة' : 'غير متوفرة' }}</p>
                            <p v-if="farm.description" class="text-brand-700">{{ farm.description }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <section id="harvests-section" class="scroll-mt-28 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المحاصيل</h2>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800 transition hover:bg-brand-200"
                            @click="scrollToTop"
                        >
                            للأعلى
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6" />
                            </svg>
                        </button>
                        <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ formatAdsCount(harvests.length) }}</span>
                    </div>
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
                                سيكون جاهزًا خلال {{ formatReadyDays(harvest.ready_in_days) }}
                            </p>
                            <p v-if="harvest.description" class="text-brand-700">{{ harvest.description }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <section id="equipment-section" class="scroll-mt-28 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-brand-900">المعدات الزراعية</h2>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800 transition hover:bg-brand-200"
                            @click="scrollToTop"
                        >
                            للأعلى
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6" />
                            </svg>
                        </button>
                        <span class="rounded-full bg-brand-gold px-3 py-1 text-xs font-bold text-white">{{ formatAdsCount(equipment.length) }}</span>
                    </div>
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
            </div>
        </main>

        <footer id="site-footer" class="mt-10 border-t border-emerald-200 bg-gradient-to-l from-emerald-900 via-emerald-800 to-emerald-700 text-white">
            <div class="mx-auto grid w-full max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <h3 class="text-xl font-extrabold">مزارع تهامة</h3>
                    <p class="mt-2 text-sm text-emerald-50">مشروع أجاويد ٤ لدعم المزارعين وعرض فرصهم للمستثمرين في السعودية.</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
                        <span class="rounded-full bg-white/15 px-3 py-1">إعلانات مزارع</span>
                        <span class="rounded-full bg-white/15 px-3 py-1">إعلانات محاصيل</span>
                        <span class="rounded-full bg-white/15 px-3 py-1">معدات زراعية</span>
                    </div>
                    <div class="mt-5 flex flex-col gap-2 border-t border-white/20 pt-4 text-xs text-emerald-100 sm:flex-row sm:items-center sm:justify-between">
                        <p>© 2026 مزارع تهامة. جميع الحقوق محفوظة.</p>
                        <a
                            href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/details/PDPL"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-bold underline decoration-white/60 underline-offset-4 hover:text-white"
                        >
                            سياسة الخصوصية: نلتزم بنظام حماية البيانات الشخصية في المملكة
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        <div v-if="showScrollTopButton" class="fixed left-4 z-40 sm:left-6" :style="{ bottom: `${scrollTopButtonBottom}px` }">
            <button
                type="button"
                class="inline-flex h-14 w-14 animate-bounce items-center justify-center rounded-full bg-emerald-700/80 text-white shadow-lg backdrop-blur-sm transition hover:bg-emerald-600/90"
                aria-label="العودة إلى الأعلى"
                @click="scrollToTop"
            >
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 15l-6-6-6 6" />
                </svg>
            </button>
        </div>

        <Modal :show="showAuthPromptModal" max-width="md" @close="showAuthPromptModal = false">
            <div dir="rtl">
                <div class="flex items-center justify-between border-b border-brand-100 px-6 py-4">
                    <h3 class="text-lg font-bold text-brand-900">يامرحبا فيك</h3>
                    <button class="text-xl leading-none text-brand-700 hover:text-brand-900" aria-label="إغلاق" @click="showAuthPromptModal = false">×</button>
                </div>

                <div class="px-6 py-5">
                    <p class="text-sm text-brand-700">اذا حبيت تعلن سجل دخولك او افتح حساب بسهولة معنا</p>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-brand-100 px-6 py-4">
                    <Link :href="route('login')" class="rounded-lg border border-brand-200 px-4 py-2 text-sm font-bold text-brand-700">تسجيل الدخول</Link>
                    <Link :href="route('register')" class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white">إنشاء حساب</Link>
                </div>
            </div>
        </Modal>

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
