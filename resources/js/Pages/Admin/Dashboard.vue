<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    initialPanel: { type: String, default: 'stats' },
    stats: { type: Object, required: true },
    users: { type: Array, default: () => [] },
    recentFarms: { type: Array, default: () => [] },
    recentHarvests: { type: Array, default: () => [] },
    recentEquipment: { type: Array, default: () => [] },
});

const page = usePage();
const isSidebarOpen = ref(false);
const activePanel = ref(props.initialPanel || 'stats');
const harvestFilter = ref('all');
const formatArabicNumber = (value) => new Intl.NumberFormat('ar-SA').format(Number(value) || 0);
const formatReadyDays = (days) => {
    const value = Number(days) || 0;

    if (value === 1) return `${formatArabicNumber(value)} يوم`;
    if (value === 2) return 'يومين';
    if (value <= 10) return `${formatArabicNumber(value)} ايام`;
    return `${formatArabicNumber(value)} أيام`;
};

const showSuccessModal = ref(Boolean(page.props.flash?.success));
const successModalMessage = ref(page.props.flash?.success || 'تمت العملية بنجاح.');
const showProcessingModal = ref(false);
const processingModalTitle = ref('جاري التحديث');
const processingModalMessage = ref('يرجى الانتظار...');
const deleteTarget = ref(null);
const returnAfterSavePanel = ref(null);
const profileAvatarInput = ref(null);
const profileAvatarPreview = ref('');
const profileAttemptedSubmit = ref(false);
const passwordAttemptedSubmit = ref(false);

const editScreen = ref({
    type: null,
    id: null,
    returnPanel: null,
});

const farmEditForm = useForm({
    farm_name: '',
    farmer_name: '',
    phone: '',
    location_text: '',
    length: '',
    width: '',
    has_well: false,
    has_electricity: false,
    description: '',
    image: null,
});

const harvestEditForm = useForm({
    harvest_name: '',
    farmer_name: '',
    phone: '',
    location_text: '',
    ready_status: 'now',
    ready_date: '',
    description: '',
    image: null,
});

const equipmentEditForm = useForm({
    product_name: '',
    seller_name: '',
    phone: '',
    location_text: '',
    price: '',
    description: '',
    image: null,
});

const profileForm = useForm({
    name: page.props.auth.user.name ?? '',
    email: page.props.auth.user.email ?? '',
    avatar: null,
    remove_avatar: false,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const normalizeSaudiPhone = (value) => {
    const englishDigits = (value || '').replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d).toString());
    let normalized = englishDigits.replace(/[\s\-\(\)]/g, '');

    if (normalized.startsWith('+966')) normalized = `0${normalized.slice(4)}`;
    else if (normalized.startsWith('00966')) normalized = `0${normalized.slice(5)}`;
    else if (normalized.startsWith('966')) normalized = `0${normalized.slice(3)}`;
    else if (normalized.startsWith('5') && normalized.length === 9) normalized = `0${normalized}`;

    return normalized;
};
const isSaudiPhone = (value) => /^05\d{8}$/.test(normalizeSaudiPhone(value));
const isEmail = (value) => emailPattern.test((value || '').trim());
const isEmailOrSaudiPhone = (value) => {
    const text = (value || '').trim();

    if (!text) return false;
    if (text.includes('@')) return isEmail(text);
    return isSaudiPhone(text);
};

const profileClientErrors = computed(() => ({
    name: profileAttemptedSubmit.value
        ? (profileForm.name?.trim() ? '' : 'الاسم مطلوب.')
        : '',
    email: profileAttemptedSubmit.value
        ? ((profileForm.email || '').trim()
            ? (isEmailOrSaudiPhone(profileForm.email) ? '' : 'أدخل بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.')
            : 'البريد الإلكتروني أو رقم الجوال مطلوب.')
        : '',
}));

const passwordClientErrors = computed(() => ({
    current_password: passwordAttemptedSubmit.value
        ? (passwordForm.current_password ? '' : 'كلمة المرور الحالية مطلوبة.')
        : '',
    password: passwordAttemptedSubmit.value
        ? (passwordForm.password
            ? (passwordForm.password.length >= 8 ? '' : 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.')
            : 'كلمة المرور الجديدة مطلوبة.')
        : '',
    password_confirmation: passwordAttemptedSubmit.value
        ? (passwordForm.password_confirmation
            ? (passwordForm.password === passwordForm.password_confirmation ? '' : 'تأكيد كلمة المرور غير مطابق.')
            : 'تأكيد كلمة المرور الجديدة مطلوب.')
        : '',
}));

const profileAvatarUrl = computed(() => {
    if (profileAvatarPreview.value) return profileAvatarPreview.value;
    if (profileForm.remove_avatar) return '';
    return page.props.auth.user?.avatar_url || '';
});

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const runWithProcessingModal = async ({ title, message, networkMessage, executeRequest }) => {
    processingModalTitle.value = title;
    processingModalMessage.value = message;
    showProcessingModal.value = true;

    const request = new Promise((resolve) => {
        let resultType = 'unknown';

        executeRequest({
            onSuccess: () => {
                resultType = 'success';
                resolve(resultType);
            },
            onError: () => {
                resultType = 'validation';
                resolve(resultType);
            },
            onCancel: () => {
                resultType = 'network';
                resolve(resultType);
            },
            onFinish: () => {
                if (resultType === 'unknown') {
                    resolve(resultType);
                }
            },
        });
    });

    const [result] = await Promise.all([request, sleep(2000)]);
    showProcessingModal.value = false;

    if (result === 'network' || result === 'unknown') {
        successModalMessage.value = networkMessage;
        showSuccessModal.value = true;
    }

    return result;
};

const sidebarItems = computed(() => [
    { key: 'stats', label: 'الإحصائيات', count: null },
    { key: 'profile', label: 'الملف الشخصي', count: null },
    { key: 'farms', label: 'المزارع', count: props.stats.farms_count },
    { key: 'harvests', label: 'المحاصيل', count: props.stats.harvests_count },
    { key: 'equipment', label: 'المعدات', count: props.stats.equipment_count },
    { key: 'users', label: 'المستخدمون', count: props.stats.users_count },
]);

const panelTitle = computed(() => {
    if (activePanel.value === 'edit') {
        return 'تعديل الإعلان';
    }

    return {
        stats: 'مؤشرات النظام',
        profile: 'الملف الشخصي',
        farms: 'إعلانات المزارع',
        harvests: 'إعلانات المحاصيل',
        equipment: 'إعلانات المعدات',
        users: 'إدارة المستخدمين',
    }[activePanel.value];
});

const filteredHarvests = computed(() => {
    if (harvestFilter.value === 'all') return props.recentHarvests;
    return props.recentHarvests.filter((item) => item.ready_status === harvestFilter.value);
});

const resetEditForms = () => {
    farmEditForm.reset();
    harvestEditForm.reset();
    equipmentEditForm.reset();
    farmEditForm.clearErrors();
    harvestEditForm.clearErrors();
    equipmentEditForm.clearErrors();
};

const selectPanel = (panelKey) => {
    activePanel.value = panelKey;
    isSidebarOpen.value = false;
    editScreen.value = { type: null, id: null, returnPanel: null };
    resetEditForms();
};

const openEditScreen = (type, item, panelKey) => {
    editScreen.value = {
        type,
        id: item.id,
        returnPanel: panelKey,
    };

    if (type === 'farm') {
        farmEditForm.defaults({
            farm_name: item.farm_name ?? '',
            farmer_name: item.farmer_name ?? '',
            phone: item.phone ?? '',
            location_text: item.location_text ?? '',
            length: item.length ?? '',
            width: item.width ?? '',
            has_well: !!item.has_well,
            has_electricity: !!item.has_electricity,
            description: item.description ?? '',
            image: null,
        });
        farmEditForm.reset();
    }

    if (type === 'harvest') {
        harvestEditForm.defaults({
            harvest_name: item.harvest_name ?? '',
            farmer_name: item.farmer_name ?? '',
            phone: item.phone ?? '',
            location_text: item.location_text ?? '',
            ready_status: item.ready_status ?? 'now',
            ready_date: item.ready_status === 'future' ? (item.ready_date ?? '') : '',
            description: item.description ?? '',
            image: null,
        });
        harvestEditForm.reset();
    }

    if (type === 'equipment') {
        equipmentEditForm.defaults({
            product_name: item.product_name ?? '',
            seller_name: item.seller_name ?? '',
            phone: item.phone ?? '',
            location_text: item.location_text ?? '',
            price: item.price ?? '',
            description: item.description ?? '',
            image: null,
        });
        equipmentEditForm.reset();
    }

    activePanel.value = 'edit';
};

const cancelEdit = () => {
    const returnPanel = editScreen.value.returnPanel || 'stats';
    editScreen.value = { type: null, id: null, returnPanel: null };
    resetEditForms();
    activePanel.value = returnPanel;
};

const submitAdminEdit = () => {
    if (!editScreen.value.type || !editScreen.value.id) {
        return;
    }

    const routeMap = {
        farm: 'farms.update',
        harvest: 'harvests.update',
        equipment: 'equipment.update',
    };

    const formMap = {
        farm: farmEditForm,
        harvest: harvestEditForm,
        equipment: equipmentEditForm,
    };

    const form = formMap[editScreen.value.type];

    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route(routeMap[editScreen.value.type], editScreen.value.id), {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                returnAfterSavePanel.value = editScreen.value.returnPanel || 'stats';
                successModalMessage.value = page.props.flash?.success || 'تم تحديث البيانات بنجاح.';
                showSuccessModal.value = true;
            },
        });
};

const submitProfileUpdate = async () => {
    profileAttemptedSubmit.value = true;
    profileForm.clearErrors();

    if (profileClientErrors.value.name || profileClientErrors.value.email) {
        return;
    }

    const result = await runWithProcessingModal({
        title: 'جاري تحديث الملف الشخصي',
        message: 'يرجى الانتظار...',
        networkMessage: 'تعذر تحديث الملف الشخصي بسبب الاتصال. حاول مرة أخرى.',
        executeRequest: ({ onSuccess, onError, onCancel, onFinish }) => {
            profileForm
                .transform((data) => ({ ...data, _method: 'patch' }))
                .post(route('profile.update'), {
                    forceFormData: true,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess,
                    onError,
                    onCancel,
                    onFinish,
                });
        },
    });

    if (result !== 'success') {
        return;
    }

    returnAfterSavePanel.value = 'profile';
    successModalMessage.value = page.props.flash?.success || 'تم تحديث معلومات الملف الشخصي.';
    showSuccessModal.value = true;
    profileForm.avatar = null;
    profileForm.remove_avatar = false;
    clearProfileAvatarSelection();
};

const submitPasswordUpdate = async () => {
    passwordAttemptedSubmit.value = true;
    passwordForm.clearErrors();

    if (passwordClientErrors.value.current_password || passwordClientErrors.value.password || passwordClientErrors.value.password_confirmation) {
        return;
    }

    const result = await runWithProcessingModal({
        title: 'جاري تحديث كلمة المرور',
        message: 'يرجى الانتظار...',
        networkMessage: 'تعذر تحديث كلمة المرور بسبب الاتصال. حاول مرة أخرى.',
        executeRequest: ({ onSuccess, onError, onCancel, onFinish }) => {
            passwordForm.put(route('password.update'), {
                preserveScroll: true,
                preserveState: true,
                onSuccess,
                onError,
                onCancel,
                onFinish,
            });
        },
    });

    if (result !== 'success') {
        return;
    }

    passwordForm.reset();
    returnAfterSavePanel.value = 'profile';
    successModalMessage.value = 'تم تحديث كلمة المرور بنجاح.';
    showSuccessModal.value = true;
};

const requestDelete = (target) => {
    deleteTarget.value = target;
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;

    const target = deleteTarget.value;
    deleteTarget.value = null;

    if (target.type === 'user') {
        router.delete(route('users.destroy', target.id));
        return;
    }

    const routeName = {
        farm: 'farms.destroy',
        harvest: 'harvests.destroy',
        equipment: 'equipment.destroy',
    }[target.type];

    router.delete(route(routeName, target.id));
};

const onProfileAvatarSelected = (event) => {
    const file = event.target.files?.[0] ?? null;

    profileForm.avatar = file;
    profileForm.remove_avatar = false;

    if (profileAvatarPreview.value) {
        URL.revokeObjectURL(profileAvatarPreview.value);
    }

    profileAvatarPreview.value = file ? URL.createObjectURL(file) : '';
};

const clearProfileAvatarSelection = () => {
    profileForm.avatar = null;

    if (profileAvatarPreview.value) {
        URL.revokeObjectURL(profileAvatarPreview.value);
    }

    profileAvatarPreview.value = '';

    if (profileAvatarInput.value) {
        profileAvatarInput.value.value = '';
    }
};

const clearProfileAvatar = () => {
    clearProfileAvatarSelection();
    profileForm.remove_avatar = true;
};

const submitLogout = async () => {
    processingModalTitle.value = 'جاري تسجيل الخروج';
    processingModalMessage.value = 'يرجى الانتظار...';
    showProcessingModal.value = true;
    await sleep(1200);

    router.post(route('logout'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.href = route('home');
        },
        onError: () => {
            showProcessingModal.value = false;
            successModalMessage.value = 'تعذر تسجيل الخروج بسبب الاتصال. حاول مرة أخرى.';
            showSuccessModal.value = true;
        },
        onFinish: () => {
            showProcessingModal.value = false;
        },
    });
};

const acknowledgeSave = () => {
    showSuccessModal.value = false;
    const panel = returnAfterSavePanel.value || editScreen.value.returnPanel || 'stats';
    returnAfterSavePanel.value = null;
    editScreen.value = { type: null, id: null, returnPanel: null };
    resetEditForms();
    activePanel.value = panel;
};

onMounted(() => {
    if (window.matchMedia('(max-width: 1023px)').matches && props.initialPanel === 'stats') {
        activePanel.value = 'stats';
    }
});

onBeforeUnmount(() => {
    if (profileAvatarPreview.value) {
        URL.revokeObjectURL(profileAvatarPreview.value);
    }
});
</script>

<template>
    <Head title="لوحة إدارة النظام" />

    <div class="min-h-screen bg-brand-bg" dir="rtl">
        <div class="grid min-h-screen lg:grid-cols-[280px_1fr]">
            <aside class="hidden h-screen flex-col border-l border-brand-100 bg-brand-900 text-white lg:flex">
                <div class="border-b border-white/10 px-5 py-6">
                    <h1 class="text-lg font-extrabold">لوحة إدارة النظام</h1>
                    <p class="mt-1 text-xs text-white/80">تحكم كامل ومراقبة مركزية</p>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto px-4 py-4">
                    <button
                        v-for="item in sidebarItems"
                        :key="item.key"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition"
                        :class="activePanel === item.key ? 'bg-white text-brand-900' : 'bg-white/10 text-white hover:bg-white/20'"
                        @click="selectPanel(item.key)"
                    >
                        <span>{{ item.label }}</span>
                        <span v-if="item.count !== null" class="rounded-full bg-black/20 px-2 py-0.5 text-xs">{{ item.count }}</span>
                    </button>

                    <Link :href="route('home')" class="mt-4 flex w-full items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20">
                        عرض الموقع الرئيسي
                    </Link>

                    <button
                        type="button"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-500"
                        @click="submitLogout"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 17l5-5-5-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12H9" />
                        </svg>
                        خروج
                    </button>
                </div>
            </aside>

            <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="isSidebarOpen = false"></div>

            <aside
                class="fixed inset-y-0 right-0 z-50 w-72 transform border-l border-brand-100 bg-brand-900 text-white transition-transform duration-300 lg:hidden"
                :class="isSidebarOpen ? 'translate-x-0' : 'translate-x-full'"
            >
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-6">
                    <div>
                        <h1 class="text-lg font-extrabold">لوحة إدارة النظام</h1>
                        <p class="mt-1 text-xs text-white/80">تحكم كامل ومراقبة مركزية</p>
                    </div>
                    <button class="rounded-lg bg-white/10 px-2 py-1 text-sm" @click="isSidebarOpen = false">✕</button>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto px-4 py-4">
                    <button
                        v-for="item in sidebarItems"
                        :key="`mobile-${item.key}`"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm font-semibold transition"
                        :class="activePanel === item.key ? 'bg-white text-brand-900' : 'bg-white/10 text-white hover:bg-white/20'"
                        @click="selectPanel(item.key)"
                    >
                        <span>{{ item.label }}</span>
                        <span v-if="item.count !== null" class="rounded-full bg-black/20 px-2 py-0.5 text-xs">{{ item.count }}</span>
                    </button>

                    <Link :href="route('home')" class="mt-4 flex w-full items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20">
                        عرض الموقع الرئيسي
                    </Link>

                    <button
                        type="button"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-500"
                        @click="submitLogout"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 17l5-5-5-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12H9" />
                        </svg>
                        خروج
                    </button>
                </div>
            </aside>

            <main class="h-screen overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="mb-3 lg:hidden">
                    <div class="relative flex min-h-[104px] items-center rounded-2xl border border-brand-100 bg-white px-4 py-5 shadow-sm">
                        <button class="absolute left-3 p-2 text-emerald-700" @click="isSidebarOpen = true" aria-label="فتح القائمة">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div class="absolute right-3 inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-brand-100 bg-white p-1">
                            <ApplicationLogo class="h-full w-full rounded-full object-cover" />
                        </div>

                        <span class="mx-auto text-sm font-extrabold text-brand-900">لوحة الادارة</span>
                    </div>
                </div>

                <div class="mb-5 rounded-2xl border border-brand-100 bg-white px-5 py-4 shadow-sm">
                    <h2 class="text-2xl font-extrabold text-brand-900">{{ panelTitle }}</h2>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
                    <div v-if="activePanel === 'edit'" class="rounded-2xl border border-brand-100 bg-brand-bg p-6">
                        <form v-if="editScreen.type === 'farm'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitAdminEdit">
                            <div><label>اسم المزرعة</label><input v-model="farmEditForm.farm_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.farm_name" /></div>
                            <div><label>اسم المزارع</label><input v-model="farmEditForm.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.farmer_name" /></div>
                            <div><label>رقم الهاتف</label><input v-model="farmEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.phone" /></div>
                            <div><label>الموقع</label><input v-model="farmEditForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.location_text" /></div>
                            <div><label>الطول</label><input v-model="farmEditForm.length" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.length" /></div>
                            <div><label>العرض</label><input v-model="farmEditForm.width" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.width" /></div>
                            <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
                                <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-white px-3 py-2">
                                    <span class="text-sm font-semibold text-brand-900">يوجد بئر</span>
                                    <span class="relative inline-flex h-6 w-11 items-center">
                                        <input v-model="farmEditForm.has_well" type="checkbox" class="peer sr-only" />
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                                        <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                                    </span>
                                </label>
                                <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-white px-3 py-2">
                                    <span class="text-sm font-semibold text-brand-900">توجد كهرباء</span>
                                    <span class="relative inline-flex h-6 w-11 items-center">
                                        <input v-model="farmEditForm.has_electricity" type="checkbox" class="peer sr-only" />
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                                        <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                                    </span>
                                </label>
                            </div>
                            <div class="md:col-span-2"><label>الوصف</label><textarea v-model="farmEditForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="farmEditForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold">هل تريد تغيير الصورة ؟</label>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <label class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
                                        اختر صورة
                                        <input type="file" accept="image/*" class="hidden" @change="farmEditForm.image = $event.target.files[0]" />
                                    </label>
                                    <span class="text-sm text-brand-700">{{ farmEditForm.image ? farmEditForm.image.name : 'لم يتم اختيار ملف' }}</span>
                                </div>
                                <InputError :message="farmEditForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2" @click="cancelEdit">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 font-bold text-white" :disabled="farmEditForm.processing">حفظ التعديلات</button>
                            </div>
                        </form>

                        <form v-if="editScreen.type === 'harvest'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitAdminEdit">
                            <div><label>اسم المحصول</label><input v-model="harvestEditForm.harvest_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.harvest_name" /></div>
                            <div><label>اسم المزارع</label><input v-model="harvestEditForm.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.farmer_name" /></div>
                            <div><label>رقم الهاتف</label><input v-model="harvestEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.phone" /></div>
                            <div><label>الموقع</label><input v-model="harvestEditForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.location_text" /></div>
                            <div class="md:col-span-2 flex gap-6 text-sm">
                                <label class="flex items-center gap-2"><input v-model="harvestEditForm.ready_status" type="radio" value="now" />جاهز الآن</label>
                                <label class="flex items-center gap-2"><input v-model="harvestEditForm.ready_status" type="radio" value="future" />جاهز لاحقًا</label>
                            </div>
                            <div v-if="harvestEditForm.ready_status === 'future'" class="md:col-span-2">
                                <label>تاريخ الجاهزية</label>
                                <input v-model="harvestEditForm.ready_date" type="date" lang="ar" dir="rtl" class="mt-1 w-full rounded-lg border-brand-100" />
                                <InputError :message="harvestEditForm.errors.ready_date" />
                            </div>
                            <div class="md:col-span-2"><label>الوصف</label><textarea v-model="harvestEditForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="harvestEditForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold">هل تريد تغيير الصورة ؟</label>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <label class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
                                        اختر صورة
                                        <input type="file" accept="image/*" class="hidden" @change="harvestEditForm.image = $event.target.files[0]" />
                                    </label>
                                    <span class="text-sm text-brand-700">{{ harvestEditForm.image ? harvestEditForm.image.name : 'لم يتم اختيار ملف' }}</span>
                                </div>
                                <InputError :message="harvestEditForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2" @click="cancelEdit">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 font-bold text-white" :disabled="harvestEditForm.processing">حفظ التعديلات</button>
                            </div>
                        </form>

                        <form v-if="editScreen.type === 'equipment'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitAdminEdit">
                            <div><label>اسم المنتج</label><input v-model="equipmentEditForm.product_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.product_name" /></div>
                            <div><label>اسم البائع</label><input v-model="equipmentEditForm.seller_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.seller_name" /></div>
                            <div><label>رقم الهاتف</label><input v-model="equipmentEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.phone" /></div>
                            <div><label>الموقع</label><input v-model="equipmentEditForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.location_text" /></div>
                            <div><label>السعر</label><input v-model="equipmentEditForm.price" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.price" /></div>
                            <div class="md:col-span-2"><label>الوصف</label><textarea v-model="equipmentEditForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="equipmentEditForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold">هل تريد تغيير الصورة ؟</label>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <label class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
                                        اختر صورة
                                        <input type="file" accept="image/*" class="hidden" @change="equipmentEditForm.image = $event.target.files[0]" />
                                    </label>
                                    <span class="text-sm text-brand-700">{{ equipmentEditForm.image ? equipmentEditForm.image.name : 'لم يتم اختيار ملف' }}</span>
                                </div>
                                <InputError :message="equipmentEditForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2" @click="cancelEdit">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 font-bold text-white" :disabled="equipmentEditForm.processing">حفظ التعديلات</button>
                            </div>
                        </form>
                    </div>

                    <div v-if="activePanel === 'profile'" class="grid gap-4 xl:grid-cols-2">
                        <section class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
                            <h3 class="text-lg font-bold text-brand-900">تحديث معلومات الملف الشخصي</h3>
                            <form class="mt-4 space-y-4" novalidate @submit.prevent="submitProfileUpdate">
                                <div>
                                    <label class="text-sm font-semibold">الاسم *</label>
                                    <input v-model="profileForm.name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                                    <InputError :message="profileForm.errors.name || profileClientErrors.name" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold">البريد الإلكتروني أو رقم الجوال *</label>
                                    <input v-model="profileForm.email" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                                    <InputError :message="profileForm.errors.email || profileClientErrors.email" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold">الصورة الشخصية</label>
                                    <div class="mt-2 flex flex-wrap items-center gap-3">
                                        <input id="admin-profile-avatar-input" ref="profileAvatarInput" type="file" accept="image/*" class="hidden" @change="onProfileAvatarSelected" />
                                        <label v-if="!profileAvatarUrl" for="admin-profile-avatar-input" class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
                                            اضغط هنا لاضافة صورة
                                        </label>
                                        <template v-else>
                                            <div class="flex flex-col items-start gap-2">
                                                <img :src="profileAvatarUrl" alt="الصورة الشخصية" class="h-20 w-20 rounded-full border border-brand-100 object-cover" />
                                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" @click="clearProfileAvatar">
                                                    حذف الصورة
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <InputError :message="profileForm.errors.avatar" class="mt-1" />
                                </div>
                                <div class="flex justify-end">
                                    <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" :disabled="profileForm.processing">
                                        حفظ التعديلات
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
                            <h3 class="text-lg font-bold text-brand-900">تحديث كلمة المرور</h3>
                            <form class="mt-4 space-y-4" novalidate @submit.prevent="submitPasswordUpdate">
                                <div>
                                    <label class="text-sm font-semibold">كلمة المرور الحالية *</label>
                                    <input v-model="passwordForm.current_password" type="password" class="mt-1 w-full rounded-lg border-brand-100" />
                                    <InputError :message="passwordForm.errors.current_password || passwordClientErrors.current_password" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold">كلمة المرور الجديدة *</label>
                                    <input v-model="passwordForm.password" type="password" class="mt-1 w-full rounded-lg border-brand-100" />
                                    <InputError :message="passwordForm.errors.password || passwordClientErrors.password" class="mt-1" />
                                </div>
                                <div>
                                    <label class="text-sm font-semibold">تأكيد كلمة المرور الجديدة *</label>
                                    <input v-model="passwordForm.password_confirmation" type="password" class="mt-1 w-full rounded-lg border-brand-100" />
                                    <InputError :message="passwordForm.errors.password_confirmation || passwordClientErrors.password_confirmation" class="mt-1" />
                                </div>
                                <div class="flex justify-end">
                                    <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" :disabled="passwordForm.processing">
                                        تحديث كلمة المرور
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <div v-if="activePanel === 'stats'" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-brand-100 bg-brand-bg p-4"><p class="text-sm text-brand-700">إجمالي المستخدمين</p><p class="mt-1 text-2xl font-extrabold text-brand-900">{{ stats.users_count }}</p></div>
                        <div class="rounded-xl border border-brand-100 bg-brand-bg p-4"><p class="text-sm text-brand-700">إجمالي المزارع</p><p class="mt-1 text-2xl font-extrabold text-brand-900">{{ stats.farms_count }}</p></div>
                        <div class="rounded-xl border border-brand-100 bg-brand-bg p-4"><p class="text-sm text-brand-700">إجمالي المحاصيل</p><p class="mt-1 text-2xl font-extrabold text-brand-900">{{ stats.harvests_count }}</p></div>
                        <div class="rounded-xl border border-brand-100 bg-brand-bg p-4"><p class="text-sm text-brand-700">إجمالي المعدات</p><p class="mt-1 text-2xl font-extrabold text-brand-900">{{ stats.equipment_count }}</p></div>
                        <div class="rounded-xl border border-brand-100 bg-emerald-50 p-4"><p class="text-sm text-emerald-700">محاصيل جاهزة الآن</p><p class="mt-1 text-2xl font-extrabold text-emerald-800">{{ stats.harvest_ready_now }}</p></div>
                        <div class="rounded-xl border border-brand-100 bg-amber-50 p-4"><p class="text-sm text-amber-700">محاصيل جاهزة لاحقًا</p><p class="mt-1 text-2xl font-extrabold text-amber-800">{{ stats.harvest_ready_future }}</p></div>
                    </div>

                    <div v-if="activePanel === 'farms'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="farm in recentFarms" :key="`admin-farm-${farm.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                            <img :src="farm.image_url" alt="farm" class="h-44 w-full object-cover" />
                            <div class="space-y-2 p-4 text-sm">
                                <p class="font-bold text-brand-900">{{ farm.farm_name }}</p>
                                <p><strong>المزارع:</strong> {{ farm.farmer_name }}</p>
                                <p><strong>الهاتف:</strong> {{ farm.phone }}</p>
                                <p><strong>الموقع:</strong> {{ farm.location_text }}</p>
                                <p><strong>المساحة:</strong> {{ farm.area }} م² ({{ farm.length }} × {{ farm.width }})</p>
                                <p class="text-xs text-gray-500">الناشر: {{ farm.owner_name }} | {{ farm.created_at }}</p>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-brand-700 px-3 py-1 text-xs font-bold text-white" @click="openEditScreen('farm', farm, 'farms')">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                        </svg>
                                        تعديل
                                    </button>
                                    <button class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="requestDelete({ type: 'farm', id: farm.id, label: farm.farm_name })">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                        </svg>
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-if="activePanel === 'harvests'" class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <button class="rounded-lg px-3 py-1 text-xs font-bold" :class="harvestFilter === 'all' ? 'bg-brand-900 text-white' : 'bg-brand-bg text-brand-700'" @click="harvestFilter = 'all'">الكل</button>
                            <button class="rounded-lg px-3 py-1 text-xs font-bold" :class="harvestFilter === 'now' ? 'bg-brand-900 text-white' : 'bg-brand-bg text-brand-700'" @click="harvestFilter = 'now'">جاهز الآن</button>
                            <button class="rounded-lg px-3 py-1 text-xs font-bold" :class="harvestFilter === 'future' ? 'bg-brand-900 text-white' : 'bg-brand-bg text-brand-700'" @click="harvestFilter = 'future'">جاهز لاحقًا</button>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="item in filteredHarvests" :key="`admin-harvest-${item.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                                <img :src="item.image_url" alt="harvest" class="h-44 w-full object-cover" />
                                <div class="space-y-2 p-4 text-sm">
                                    <p class="font-bold text-brand-900">{{ item.harvest_name }}</p>
                                    <p><strong>المزارع:</strong> {{ item.farmer_name }}</p>
                                    <p><strong>الهاتف:</strong> {{ item.phone }}</p>
                                    <p><strong>الموقع:</strong> {{ item.location_text }}</p>
                                    <p v-if="item.ready_status === 'future'" class="font-semibold text-amber-700">جاهز خلال {{ formatReadyDays(item.ready_in_days) }}</p>
                                    <p v-else class="font-semibold text-emerald-700">جاهز الآن</p>
                                    <p class="text-xs text-gray-500">الناشر: {{ item.owner_name }} | {{ item.created_at }}</p>
                                    <div class="mt-3 flex justify-end gap-2">
                                        <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-brand-700 px-3 py-1 text-xs font-bold text-white" @click="openEditScreen('harvest', item, 'harvests')">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                            </svg>
                                            تعديل
                                        </button>
                                        <button class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="requestDelete({ type: 'harvest', id: item.id, label: item.harvest_name })">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                            </svg>
                                            حذف
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-if="activePanel === 'equipment'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article v-for="item in recentEquipment" :key="`admin-eq-${item.id}`" class="overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                            <img :src="item.image_url" alt="equipment" class="h-44 w-full object-cover" />
                            <div class="space-y-2 p-4 text-sm">
                                <p class="font-bold text-brand-900">{{ item.product_name }}</p>
                                <p><strong>البائع:</strong> {{ item.seller_name }}</p>
                                <p><strong>الهاتف:</strong> {{ item.phone }}</p>
                                <p><strong>الموقع:</strong> {{ item.location_text }}</p>
                                <p><strong>السعر:</strong> {{ item.price }}</p>
                                <p class="text-xs text-gray-500">الناشر: {{ item.owner_name }} | {{ item.created_at }}</p>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-brand-700 px-3 py-1 text-xs font-bold text-white" @click="openEditScreen('equipment', item, 'equipment')">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                        </svg>
                                        تعديل
                                    </button>
                                    <button class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="requestDelete({ type: 'equipment', id: item.id, label: item.product_name })">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                        </svg>
                                        حذف
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-if="activePanel === 'users'" class="space-y-3">
                        <div v-for="u in users" :key="`u-${u.id}`" class="rounded-xl border border-brand-100 p-4 text-sm">
                            <p class="font-bold text-brand-900">{{ u.name }} ({{ u.role }})</p>
                            <p class="mt-1">{{ u.email }}</p>
                            <p class="mt-1 text-xs text-gray-600">مزارع: {{ u.farms_count }} | محاصيل: {{ u.harvests_count }} | معدات: {{ u.equipment_count }}</p>
                            <button v-if="u.role !== 'admin'" class="mt-3 inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="requestDelete({ type: 'user', id: u.id, label: u.name })">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                </svg>
                                حذف المستخدم
                            </button>
                        </div>
                    </div>
                </div>

                <Modal :show="!!deleteTarget" max-width="md" @close="deleteTarget = null">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-red-700">تأكيد الحذف</h3>
                        <p class="mt-2 text-sm text-brand-700">هل أنت متأكد من حذف <strong>{{ deleteTarget?.label }}</strong>؟</p>
                        <div class="mt-5 flex justify-end gap-2">
                            <button class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="deleteTarget = null">إلغاء</button>
                            <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white" @click="confirmDelete">تأكيد الحذف</button>
                        </div>
                    </div>
                </Modal>

                <Modal :show="showSuccessModal" max-width="md" @close="showSuccessModal = false">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-brand-900">تم التحديث بنجاح</h3>
                        <p class="mt-2 text-sm text-brand-700">{{ successModalMessage }}</p>
                        <div class="mt-5 flex justify-end">
                            <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" @click="acknowledgeSave">حسنًا</button>
                        </div>
                    </div>
                </Modal>

                <Modal :show="showProcessingModal" max-width="md" :closeable="false">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-brand-900">{{ processingModalTitle }}</h3>
                        <p class="mt-2 text-sm text-brand-700">{{ processingModalMessage }}</p>
                        <div class="mt-5 flex justify-center">
                            <span class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></span>
                        </div>
                    </div>
                </Modal>
            </main>
        </div>
    </div>
</template>
