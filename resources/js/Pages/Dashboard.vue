<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    initialPanel: { type: String, default: 'myads' },
    stats: { type: Object, required: true },
    myFarms: { type: Array, default: () => [] },
    myHarvests: { type: Array, default: () => [] },
    myEquipment: { type: Array, default: () => [] },
});

const page = usePage();
const isSidebarOpen = ref(false);
const activePanel = ref(props.initialPanel || 'myads');
const createTab = ref('');
const accountName = page.props.auth.user?.name ?? '';
const formatArabicNumber = (value) => new Intl.NumberFormat('ar-SA').format(Number(value) || 0);
const formatReadyDays = (days) => {
    const value = Number(days) || 0;

    if (value === 1) return `${formatArabicNumber(value)} يوم`;
    if (value === 2) return 'يومين';
    if (value <= 10) return `${formatArabicNumber(value)} ايام`;
    return `${formatArabicNumber(value)} أيام`;
};

const showSuccessModal = ref(Boolean(page.props.flash?.success));
const successModalTitle = ref('تمت العملية بنجاح');
const successModalMessage = ref(page.props.flash?.success || 'تمت العملية بنجاح.');
const showPostingModal = ref(false);
const postingModalTitle = ref('جاري إرسال الإعلان');
const postingModalMessage = ref('يرجى الانتظار...');
const deleteTarget = ref(null);
const showAccountDeleteModal = ref(false);
const returnAfterSavePanel = ref(null);
const farmImageInput = ref(null);
const harvestImageInput = ref(null);
const equipmentImageInput = ref(null);
const farmImagePreview = ref('');
const harvestImagePreview = ref('');
const equipmentImagePreview = ref('');
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

const farmForm = useForm({
    farm_name: '',
    farmer_name: accountName,
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

const harvestForm = useForm({
    harvest_name: '',
    farmer_name: accountName,
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

const equipmentForm = useForm({
    product_name: '',
    seller_name: accountName,
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

const accountDeleteForm = useForm({
    password: '',
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

const sidebarItems = computed(() => [
    { key: 'myads', label: 'إعلاناتي', count: props.myFarms.length + props.myHarvests.length + props.myEquipment.length },
    { key: 'profile', label: 'الملف الشخصي', count: null },
]);

const myAdsCombined = computed(() => ([
    ...props.myFarms.map((farm) => ({ ...farm, _type: 'farm', _title: farm.farm_name, _deleteLabel: farm.farm_name })),
    ...props.myHarvests.map((item) => ({ ...item, _type: 'harvest', _title: item.harvest_name, _deleteLabel: item.harvest_name })),
    ...props.myEquipment.map((item) => ({ ...item, _type: 'equipment', _title: item.product_name, _deleteLabel: item.product_name })),
]));

const panelTitle = computed(() => {
    if (activePanel.value === 'edit') {
        return 'تعديل الإعلان';
    }

    return {
        myads: 'إعلاناتي',
        create: 'إضافة إعلان جديد',
        profile: 'الملف الشخصي',
    }[activePanel.value];
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
    const returnPanel = editScreen.value.returnPanel || 'myads';
    editScreen.value = { type: null, id: null, returnPanel: null };
    resetEditForms();
    activePanel.value = returnPanel;
};

const submitUserEdit = () => {
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
                successModalTitle.value = 'تم التحديث بنجاح';
                returnAfterSavePanel.value = editScreen.value.returnPanel || 'myads';
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
        networkTitle: 'تعذر التحديث',
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

    successModalTitle.value = 'تم التحديث بنجاح';
    returnAfterSavePanel.value = 'profile';
    successModalMessage.value = page.props.flash?.success || 'تم تحديث معلومات الملف الشخصي.';
    showSuccessModal.value = true;
    profileForm.avatar = null;
    profileForm.remove_avatar = false;
    clearImageSelection(profileForm, profileAvatarPreview, profileAvatarInput, 'avatar');
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const runWithProcessingModal = async ({ title, message, networkTitle, networkMessage, executeRequest }) => {
    postingModalTitle.value = title;
    postingModalMessage.value = message;
    showPostingModal.value = true;

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
    showPostingModal.value = false;

    if (result === 'network' || result === 'unknown') {
        successModalTitle.value = networkTitle;
        successModalMessage.value = networkMessage;
        showSuccessModal.value = true;
    }

    return result;
};

const submitCreateFormWithWait = async (form, routeName, title, message) => {
    return runWithProcessingModal({
        title,
        message,
        networkTitle: 'تعذر الإرسال',
        networkMessage: 'تعذر إرسال الإعلان بسبب الاتصال. حاول مرة أخرى.',
        executeRequest: ({ onSuccess, onError, onCancel, onFinish }) => {
            form.post(route(routeName), {
                forceFormData: true,
                preserveScroll: true,
                onSuccess,
                onError,
                onCancel,
                onFinish,
            });
        },
    });
};

const submitFarm = async () => {
    const result = await submitCreateFormWithWait(
        farmForm,
        'farms.store',
        'جاري إرسال إعلان المزرعة',
        'يتم الآن رفع الصورة وإرسال الإعلان...'
    );

    if (result !== 'success') return;

    farmForm.reset();
    farmForm.farmer_name = accountName;
    clearImageSelection(farmForm, farmImagePreview, farmImageInput);
    activePanel.value = 'myads';
    returnAfterSavePanel.value = 'myads';
    successModalTitle.value = 'تم الارسال بنجاح';
    successModalMessage.value = page.props.flash?.success || 'تم إضافة إعلان المزرعة بنجاح.';
    showSuccessModal.value = true;
};

const submitHarvest = async () => {
    const result = await submitCreateFormWithWait(
        harvestForm,
        'harvests.store',
        'جاري إرسال إعلان المحصول',
        'يتم الآن رفع الصورة وإرسال الإعلان...'
    );

    if (result !== 'success') return;

    harvestForm.reset();
    harvestForm.farmer_name = accountName;
    harvestForm.ready_status = 'now';
    clearImageSelection(harvestForm, harvestImagePreview, harvestImageInput);
    activePanel.value = 'myads';
    returnAfterSavePanel.value = 'myads';
    successModalTitle.value = 'تم الارسال بنجاح';
    successModalMessage.value = page.props.flash?.success || 'تم إضافة إعلان المحصول بنجاح.';
    showSuccessModal.value = true;
};

const submitEquipment = async () => {
    const result = await submitCreateFormWithWait(
        equipmentForm,
        'equipment.store',
        'جاري إرسال إعلان المعدات',
        'يتم الآن رفع الصورة وإرسال الإعلان...'
    );

    if (result !== 'success') return;

    equipmentForm.reset();
    equipmentForm.seller_name = accountName;
    clearImageSelection(equipmentForm, equipmentImagePreview, equipmentImageInput);
    activePanel.value = 'myads';
    returnAfterSavePanel.value = 'myads';
    successModalTitle.value = 'تم الارسال بنجاح';
    successModalMessage.value = page.props.flash?.success || 'تم إضافة إعلان المعدات بنجاح.';
    showSuccessModal.value = true;
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
        networkTitle: 'تعذر التحديث',
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
    successModalTitle.value = 'تم التحديث بنجاح';
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

    const routeName = {
        farm: 'farms.destroy',
        harvest: 'harvests.destroy',
        equipment: 'equipment.destroy',
    }[target.type];

    router.delete(route(routeName, target.id), {
        preserveScroll: true,
        onSuccess: () => {
            returnAfterSavePanel.value = 'myads';
            successModalTitle.value = 'تم الحذف بنجاح';
            successModalMessage.value = page.props.flash?.success || 'تم حذف الإعلان بنجاح.';
            showSuccessModal.value = true;
        },
    });
};

const openAccountDeleteConfirm = () => {
    accountDeleteForm.reset();
    accountDeleteForm.clearErrors();
    showAccountDeleteModal.value = true;
};

const confirmAccountDelete = () => {
    accountDeleteForm.delete(route('profile.destroy'), {
        preserveScroll: true,
    });
};

const submitLogout = async () => {
    postingModalTitle.value = 'جاري تسجيل الخروج';
    postingModalMessage.value = 'يرجى الانتظار...';
    showPostingModal.value = true;
    await sleep(1200);

    router.post(route('logout'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.href = route('home');
        },
        onError: () => {
            showPostingModal.value = false;
            successModalTitle.value = 'تعذر تسجيل الخروج';
            successModalMessage.value = 'تعذر تسجيل الخروج بسبب الاتصال. حاول مرة أخرى.';
            showSuccessModal.value = true;
        },
        onFinish: () => {
            showPostingModal.value = false;
        },
    });
};

const acknowledgeSave = () => {
    showSuccessModal.value = false;
    const panel = returnAfterSavePanel.value || editScreen.value.returnPanel || 'myads';
    returnAfterSavePanel.value = null;
    editScreen.value = { type: null, id: null, returnPanel: null };
    resetEditForms();
    activePanel.value = panel;
};

const replacePreviewUrl = (previewRef, file) => {
    if (previewRef.value) {
        URL.revokeObjectURL(previewRef.value);
    }

    previewRef.value = file ? URL.createObjectURL(file) : '';
};

const onCreateImageSelected = (form, previewRef, event, field = 'image') => {
    const file = event.target.files?.[0] ?? null;
    form[field] = file;
    replacePreviewUrl(previewRef, file);
};

const clearImageSelection = (form, previewRef, inputRef, field = 'image') => {
    form[field] = null;
    replacePreviewUrl(previewRef, null);

    if (inputRef.value) {
        inputRef.value.value = '';
    }
};

const onFarmImageSelected = (event) => onCreateImageSelected(farmForm, farmImagePreview, event);
const onHarvestImageSelected = (event) => onCreateImageSelected(harvestForm, harvestImagePreview, event);
const onEquipmentImageSelected = (event) => onCreateImageSelected(equipmentForm, equipmentImagePreview, event);
const onProfileAvatarSelected = (event) => {
    onCreateImageSelected(profileForm, profileAvatarPreview, event, 'avatar');
    profileForm.remove_avatar = false;
};

const clearFarmImage = () => clearImageSelection(farmForm, farmImagePreview, farmImageInput);
const clearHarvestImage = () => clearImageSelection(harvestForm, harvestImagePreview, harvestImageInput);
const clearEquipmentImage = () => clearImageSelection(equipmentForm, equipmentImagePreview, equipmentImageInput);
const clearProfileAvatar = () => {
    clearImageSelection(profileForm, profileAvatarPreview, profileAvatarInput, 'avatar');
    profileForm.remove_avatar = true;
};

onBeforeUnmount(() => {
    [farmImagePreview, harvestImagePreview, equipmentImagePreview, profileAvatarPreview].forEach((previewRef) => {
        if (previewRef.value) {
            URL.revokeObjectURL(previewRef.value);
        }
    });
});
</script>

<template>
    <Head title="لوحة المستخدم" />

    <div class="min-h-screen bg-brand-bg" dir="rtl">
        <div class="grid min-h-screen lg:grid-cols-[280px_1fr]">
            <aside class="hidden h-screen flex-col border-l border-brand-100 bg-brand-900 text-white lg:flex">
                <div class="border-b border-white/10 px-5 py-6">
                    <h1 class="text-lg font-extrabold">لوحة المستخدم</h1>
                    <p class="mt-1 text-xs text-white/80">إدارة إعلاناتك وحسابك</p>
                </div>

                <div class="space-y-2 px-4 py-4">
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
                </div>

                <div class="space-y-2 border-t border-white/10 px-4 py-4">
                    <Link :href="route('home')" class="flex w-full items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20">
                        العودة للموقع الرئيسي
                    </Link>

                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-500"
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
                        <h1 class="text-lg font-extrabold">لوحة المستخدم</h1>
                        <p class="mt-1 text-xs text-white/80">إدارة إعلاناتك وحسابك</p>
                    </div>
                    <button class="rounded-lg bg-white/10 px-2 py-1 text-sm" @click="isSidebarOpen = false">✕</button>
                </div>

                <div class="space-y-2 px-4 py-4">
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
                </div>

                <div class="space-y-2 border-t border-white/10 px-4 py-4">
                    <Link :href="route('home')" class="flex w-full items-center justify-center rounded-xl bg-white/10 px-3 py-2 text-sm font-bold text-white transition hover:bg-white/20">
                        العودة للموقع الرئيسي
                    </Link>

                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-red-500"
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

                        <span class="mx-auto text-sm font-extrabold text-brand-900">لوحة المستخدم</span>
                    </div>
                </div>

                <div class="mb-5 rounded-2xl border border-brand-100 bg-white px-5 py-4 shadow-sm">
                    <h2 class="text-2xl font-extrabold text-brand-900">{{ panelTitle }}</h2>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
                    <div v-if="activePanel === 'edit'" class="rounded-2xl border border-brand-100 bg-brand-bg p-6">
                        <form v-if="editScreen.type === 'farm'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitUserEdit">
                            <div><label>اسم المزرعة</label><input v-model="farmEditForm.farm_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.farm_name" /></div>
                            <div><label>اسم المزارع</label><input v-model="farmEditForm.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.farmer_name" /></div>
                            <div><label>رقم التواصل لهذا الاعلان</label><input v-model="farmEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmEditForm.errors.phone" /></div>
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

                        <form v-if="editScreen.type === 'harvest'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitUserEdit">
                            <div><label>اسم المحصول</label><input v-model="harvestEditForm.harvest_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.harvest_name" /></div>
                            <div><label>اسم المزارع</label><input v-model="harvestEditForm.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.farmer_name" /></div>
                            <div><label>رقم التواصل لهذا الاعلان</label><input v-model="harvestEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestEditForm.errors.phone" /></div>
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

                        <form v-if="editScreen.type === 'equipment'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitUserEdit">
                            <div><label>اسم المنتج</label><input v-model="equipmentEditForm.product_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.product_name" /></div>
                            <div><label>اسم البائع</label><input v-model="equipmentEditForm.seller_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.seller_name" /></div>
                            <div><label>رقم التواصل لهذا الاعلان</label><input v-model="equipmentEditForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentEditForm.errors.phone" /></div>
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
                                        <input id="profile-avatar-input" ref="profileAvatarInput" type="file" accept="image/*" class="hidden" @change="onProfileAvatarSelected" />
                                        <label v-if="!profileAvatarUrl" for="profile-avatar-input" class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
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

                        <section class="space-y-4">
                            <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
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
                            </div>

                            <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
                                <h3 class="text-lg font-bold text-red-700">حذف الحساب</h3>
                                <p class="mt-2 text-sm text-red-700">عند حذف الحساب سيتم حذف كل إعلاناتك نهائيًا.</p>
                                <button class="mt-3 inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="openAccountDeleteConfirm">حذف الحساب</button>
                            </div>
                        </section>
                    </div>

                    <div v-if="activePanel === 'myads'" class="space-y-4">
                        <div class="rounded-2xl border border-brand-100 bg-brand-bg p-6 text-center">
                            <p class="mb-4 text-sm font-semibold text-brand-700">لديك إعلان جديد؟ أضفه الآن ليظهر في المنصة.</p>
                            <button class="inline-flex items-center gap-2 rounded-xl bg-brand-900 px-6 py-3 text-sm font-bold text-white" @click="activePanel = 'create'; createTab = ''">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                                </svg>
                                نشر إعلان جديد
                            </button>
                        </div>

                        <div v-if="myAdsCombined.length === 0" class="rounded-xl border border-brand-100 p-4 text-sm text-brand-700">لا توجد إعلانات حتى الآن.</div>

                        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="item in myAdsCombined" :key="`user-ad-${item._type}-${item.id}`" class="flex h-full flex-col overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm">
                                <img :src="item.image_url" :alt="item._type" class="h-44 w-full object-cover" />
                                <div class="flex h-full flex-col p-4 text-sm">
                                    <div class="space-y-2">
                                        <p class="font-bold text-brand-900">{{ item._title }}</p>
                                        <p v-if="item._type === 'farm' || item._type === 'harvest'"><strong>المزارع:</strong> {{ item.farmer_name }}</p>
                                        <p v-if="item._type === 'equipment'"><strong>البائع:</strong> {{ item.seller_name }}</p>
                                        <p><strong>الهاتف:</strong> {{ item.phone }}</p>
                                        <p><strong>الموقع:</strong> {{ item.location_text }}</p>
                                        <p v-if="item._type === 'farm'"><strong>المساحة:</strong> {{ item.area }} م² ({{ item.length }} × {{ item.width }})</p>
                                        <p v-if="item._type === 'farm'"><strong>البئر:</strong> {{ item.has_well ? 'متوفر' : 'غير متوفر' }}</p>
                                        <p v-if="item._type === 'farm'"><strong>الكهرباء:</strong> {{ item.has_electricity ? 'متوفرة' : 'غير متوفرة' }}</p>
                                        <p v-if="item._type === 'harvest' && item.ready_status === 'future'" class="font-semibold text-amber-700">جاهز خلال {{ formatReadyDays(item.ready_in_days) }}</p>
                                        <p v-if="item._type === 'harvest' && item.ready_status !== 'future'" class="font-semibold text-emerald-700">جاهز الآن</p>
                                        <p v-if="item._type === 'equipment'"><strong>السعر:</strong> {{ item.price }}</p>
                                    </div>

                                    <footer class="mt-auto border-t border-brand-100 pt-3" style="direction:ltr;">
                                        <div class="flex justify-start gap-2">
                                            <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-brand-700 px-3 py-1 text-xs font-bold text-white" @click="openEditScreen(item._type, item, 'myads')">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9" /><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" /></svg>
                                                تعديل
                                            </button>
                                            <button class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="requestDelete({ type: item._type, id: item.id, label: item._deleteLabel })">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" /></svg>
                                                حذف
                                            </button>
                                        </div>
                                    </footer>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-if="activePanel === 'create'" class="space-y-4">
                        <div v-if="!createTab" class="rounded-2xl border border-brand-100 bg-brand-bg p-6 text-center">
                            <p class="mb-4 text-sm font-semibold text-brand-700">اختر نوع الإعلان أولًا</p>
                            <div class="mx-auto flex w-full flex-col gap-3 md:max-w-md">
                                <button class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white" @click="createTab = 'farm'">اعلان عن مزرعة</button>
                                <button class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white" @click="createTab = 'harvest'">اعلان عن محصول</button>
                                <button class="w-full rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white" @click="createTab = 'equipment'">اعلان عن معدات</button>
                                <button class="w-full rounded-xl border border-brand-200 bg-white px-4 py-3 text-sm font-bold text-brand-800 hover:bg-brand-50" @click="selectPanel('myads')">
                                    إلغاء
                                </button>
                            </div>
                        </div>

                        <div v-else class="flex items-center justify-between">
                            <button class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="createTab = ''">عودة</button>
                            <p class="text-sm font-bold text-brand-900">
                                {{
                                    createTab === 'farm'
                                        ? 'نموذج إعلان مزرعة'
                                        : createTab === 'harvest'
                                          ? 'نموذج إعلان محصول'
                                          : 'نموذج إعلان معدات'
                                }}
                            </p>
                        </div>

                        <form v-if="createTab === 'farm'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitFarm">
                            <div><label class="text-sm font-semibold">اسم المزرعة</label><input v-model="farmForm.farm_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmForm.errors.farm_name" /></div>
                            <div><label class="text-sm font-semibold">اسم المزارع</label><input v-model="farmForm.farmer_name" readonly class="mt-1 w-full rounded-lg border-brand-100 bg-gray-50 text-gray-700" /><InputError :message="farmForm.errors.farmer_name" /></div>
                            <div><label class="text-sm font-semibold">رقم التواصل لهذا الاعلان</label><input v-model="farmForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmForm.errors.phone" /></div>
                            <div><label class="text-sm font-semibold">الموقع</label><input v-model="farmForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmForm.errors.location_text" /></div>
                            <div><label class="text-sm font-semibold">الطول</label><input v-model="farmForm.length" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmForm.errors.length" /></div>
                            <div><label class="text-sm font-semibold">العرض</label><input v-model="farmForm.width" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="farmForm.errors.width" /></div>
                            <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
                                <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-white px-3 py-2">
                                    <span class="text-sm font-semibold text-brand-900">يوجد بئر</span>
                                    <span class="relative inline-flex h-6 w-11 items-center">
                                        <input v-model="farmForm.has_well" type="checkbox" class="peer sr-only" />
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                                        <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                                    </span>
                                </label>
                                <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-white px-3 py-2">
                                    <span class="text-sm font-semibold text-brand-900">توجد كهرباء</span>
                                    <span class="relative inline-flex h-6 w-11 items-center">
                                        <input v-model="farmForm.has_electricity" type="checkbox" class="peer sr-only" />
                                        <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                                        <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                                    </span>
                                </label>
                            </div>
                            <div class="md:col-span-2"><label class="text-sm font-semibold">الوصف</label><textarea v-model="farmForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="farmForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold">اضغط هنا لاضافة صورة</label>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <input id="farm-create-image-input" ref="farmImageInput" type="file" accept="image/*" class="hidden" @change="onFarmImageSelected" />
                                    <label
                                        v-if="!farmImagePreview"
                                        for="farm-create-image-input"
                                        class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200"
                                    >
                                        اضغط هنا لاضافة صورة
                                    </label>
                                    <template v-else>
                                        <div class="flex flex-col items-start gap-2">
                                            <img :src="farmImagePreview" alt="معاينة صورة المزرعة" class="h-20 w-20 rounded-lg border border-brand-100 object-cover" />
                                            <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" @click="clearFarmImage">
                                                حذف الصورة
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <InputError :message="farmForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="activePanel = 'myads'">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" :disabled="farmForm.processing">ارسال الاعلان</button>
                            </div>
                        </form>

                        <form v-if="createTab === 'harvest'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitHarvest">
                            <div><label class="text-sm font-semibold">اسم المحصول</label><input v-model="harvestForm.harvest_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestForm.errors.harvest_name" /></div>
                            <div><label class="text-sm font-semibold">اسم المزارع</label><input v-model="harvestForm.farmer_name" readonly class="mt-1 w-full rounded-lg border-brand-100 bg-gray-50 text-gray-700" /><InputError :message="harvestForm.errors.farmer_name" /></div>
                            <div><label class="text-sm font-semibold">رقم التواصل لهذا الاعلان</label><input v-model="harvestForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestForm.errors.phone" /></div>
                            <div><label class="text-sm font-semibold">الموقع</label><input v-model="harvestForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestForm.errors.location_text" /></div>
                            <div class="md:col-span-2 rounded-xl border border-brand-100 bg-white p-3">
                                <label class="mb-2 block text-sm font-semibold text-brand-900">هل محصولك جاهز الان ام في المستقبل ؟</label>
                                <div class="relative grid grid-cols-2 rounded-full bg-slate-300 p-1">
                                    <span
                                        class="pointer-events-none absolute bottom-1 top-1 w-[calc(50%-0.25rem)] rounded-full bg-white shadow transition-all duration-300"
                                        :class="harvestForm.ready_status === 'now' ? 'right-1' : 'left-1'"
                                    ></span>

                                    <label class="relative z-10 cursor-pointer text-center">
                                        <input v-model="harvestForm.ready_status" type="radio" value="now" class="sr-only" />
                                        <span class="block px-2 py-1 text-sm font-semibold transition" :class="harvestForm.ready_status === 'now' ? 'text-emerald-700' : 'text-slate-700'">
                                            جاهز الان
                                        </span>
                                    </label>

                                    <label class="relative z-10 cursor-pointer text-center">
                                        <input v-model="harvestForm.ready_status" type="radio" value="future" class="sr-only" />
                                        <span class="block px-2 py-1 text-sm font-semibold transition" :class="harvestForm.ready_status === 'future' ? 'text-amber-700' : 'text-slate-700'">
                                            جاهز في المستقبل
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="harvestForm.ready_status === 'future'" class="md:col-span-2"><label class="text-sm font-semibold">تاريخ الجاهزية</label><input v-model="harvestForm.ready_date" type="date" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="harvestForm.errors.ready_date" /></div>
                            <div class="md:col-span-2"><label class="text-sm font-semibold">الوصف</label><textarea v-model="harvestForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="harvestForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold">اضغط هنا لاضافة صورة</label>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <input id="harvest-create-image-input" ref="harvestImageInput" type="file" accept="image/*" class="hidden" @change="onHarvestImageSelected" />
                                    <label
                                        v-if="!harvestImagePreview"
                                        for="harvest-create-image-input"
                                        class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200"
                                    >
                                        اضغط هنا لاضافة صورة
                                    </label>
                                    <template v-else>
                                        <div class="flex flex-col items-start gap-2">
                                            <img :src="harvestImagePreview" alt="معاينة صورة المحصول" class="h-20 w-20 rounded-lg border border-brand-100 object-cover" />
                                            <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" @click="clearHarvestImage">
                                                حذف الصورة
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <InputError :message="harvestForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="activePanel = 'myads'">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" :disabled="harvestForm.processing">ارسال الاعلان</button>
                            </div>
                        </form>

                        <form v-if="createTab === 'equipment'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitEquipment">
                            <div><label class="text-sm font-semibold">اسم المنتج</label><input v-model="equipmentForm.product_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentForm.errors.product_name" /></div>
                            <div><label class="text-sm font-semibold">اسم البائع</label><input v-model="equipmentForm.seller_name" readonly class="mt-1 w-full rounded-lg border-brand-100 bg-gray-50 text-gray-700" /><InputError :message="equipmentForm.errors.seller_name" /></div>
                            <div><label class="text-sm font-semibold">رقم التواصل لهذا الاعلان</label><input v-model="equipmentForm.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentForm.errors.phone" /></div>
                            <div><label class="text-sm font-semibold">الموقع</label><input v-model="equipmentForm.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentForm.errors.location_text" /></div>
                            <div><label class="text-sm font-semibold">السعر</label><input v-model="equipmentForm.price" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="equipmentForm.errors.price" /></div>
                            <div class="md:col-span-2"><label class="text-sm font-semibold">الوصف</label><textarea v-model="equipmentForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="equipmentForm.errors.description" /></div>
                            <div class="md:col-span-2">
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <input id="equipment-create-image-input" ref="equipmentImageInput" type="file" accept="image/*" class="hidden" @change="onEquipmentImageSelected" />
                                    <label
                                        v-if="!equipmentImagePreview"
                                        for="equipment-create-image-input"
                                        class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200"
                                    >
                                        اضغط هنا لاضافة صورة
                                    </label>
                                    <template v-else>
                                        <div class="flex flex-col items-start gap-2">
                                            <img :src="equipmentImagePreview" alt="معاينة صورة المنتج" class="h-20 w-20 rounded-lg border border-brand-100 object-cover" />
                                            <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700" @click="clearEquipmentImage">
                                                حذف الصورة
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <InputError :message="equipmentForm.errors.image" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="activePanel = 'myads'">إلغاء</button>
                                <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" :disabled="equipmentForm.processing">ارسال الاعلان</button>
                            </div>
                        </form>
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

                <Modal :show="showAccountDeleteModal" max-width="md" @close="showAccountDeleteModal = false">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-red-700">تأكيد حذف الحساب</h3>
                        <p class="mt-2 text-sm text-brand-700">هذه العملية نهائية وستحذف جميع إعلاناتك. أدخل كلمة المرور للتأكيد.</p>
                        <div class="mt-4">
                            <label class="text-sm font-semibold">كلمة المرور</label>
                            <input v-model="accountDeleteForm.password" type="password" class="mt-1 w-full rounded-lg border-brand-100" @keyup.enter="confirmAccountDelete" />
                            <InputError :message="accountDeleteForm.errors.password" class="mt-1" />
                        </div>
                        <div class="mt-5 flex justify-end gap-2">
                            <button class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="showAccountDeleteModal = false">إلغاء</button>
                            <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white" :disabled="accountDeleteForm.processing" @click="confirmAccountDelete">تأكيد حذف الحساب</button>
                        </div>
                    </div>
                </Modal>

                <Modal :show="showSuccessModal" max-width="md" @close="showSuccessModal = false">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-brand-900">{{ successModalTitle }}</h3>
                        <p class="mt-2 text-sm text-brand-700">{{ successModalMessage }}</p>
                        <div class="mt-5 flex justify-end">
                            <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" @click="acknowledgeSave">حسنًا</button>
                        </div>
                    </div>
                </Modal>

                <Modal :show="showPostingModal" max-width="md" :closeable="false">
                    <div class="p-6" dir="rtl">
                        <h3 class="text-lg font-bold text-brand-900">{{ postingModalTitle }}</h3>
                        <p class="mt-2 text-sm text-brand-700">{{ postingModalMessage }}</p>
                        <div class="mt-5 flex justify-center">
                            <span class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></span>
                        </div>
                    </div>
                </Modal>
            </main>
        </div>
    </div>
</template>
