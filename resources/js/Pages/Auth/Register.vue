<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const touched = ref({
    name: false,
    email: false,
    password: false,
    password_confirmation: false,
});
const attemptedSubmit = ref(false);
const isSubmitting = ref(false);
const showRegisteringModal = ref(false);
const submitIssueMessage = ref('');

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
const isEmail = (value) => emailPattern.test(value);
const shouldShow = (field) => attemptedSubmit.value || touched.value[field];

const clientErrors = computed(() => ({
    name: shouldShow('name') ? (form.name.trim() ? '' : 'الاسم مطلوب.') : '',
    email: shouldShow('email')
        ? (form.email.trim()
            ? (isEmail(form.email.trim()) || isSaudiPhone(form.email.trim()) ? '' : 'أدخل بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.')
            : 'البريد الإلكتروني أو رقم الجوال مطلوب.')
        : '',
    password: shouldShow('password') ? (form.password ? (form.password.length >= 8 ? '' : 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.') : 'كلمة المرور مطلوبة.') : '',
    password_confirmation: shouldShow('password_confirmation') && form.password
        ? (form.password === form.password_confirmation ? '' : 'تأكيد كلمة المرور غير مطابق.')
        : (shouldShow('password_confirmation') ? (form.password_confirmation ? '' : 'تأكيد كلمة المرور مطلوب.') : ''),
}));

const hasClientErrors = computed(() => Object.values(clientErrors.value).some(Boolean));
const hasUsedIdentifierError = computed(() => {
    const serverMessage = (form.errors.email || '').toString().toLowerCase();
    return serverMessage.includes('taken')
        || serverMessage.includes('already')
        || serverMessage.includes('مستخدم')
        || serverMessage.includes('مستعمل');
});

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const submit = async () => {
    attemptedSubmit.value = true;
    submitIssueMessage.value = '';

    if (hasClientErrors.value) {
        return;
    }

    isSubmitting.value = true;
    showRegisteringModal.value = true;

    const registerRequest = new Promise((resolve) => {
        let resultType = 'unknown';

        form.post(route('register'), {
            preserveScroll: true,
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

    const [result] = await Promise.all([registerRequest, sleep(2000)]);

    if (result === 'success') {
        return;
    }

    showRegisteringModal.value = false;
    isSubmitting.value = false;

    if (result === 'validation') {
        return;
    }

    submitIssueMessage.value = 'تعذر تأكيد نتيجة التسجيل بسبب الاتصال. جرّب تسجيل الدخول بنفس البيانات.';
};

const goHome = () => {
    router.get(route('home'));
};
</script>

<template>
    <GuestLayout>
        <Head title="إنشاء حساب" />

        <h1 class="mb-4 text-xl font-bold text-brand-900">إنشاء حساب جديد</h1>

        <div v-if="submitIssueMessage" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ submitIssueMessage }}
            <div class="mt-2">
                <Link :href="route('login')" class="font-bold underline">الانتقال إلى تسجيل الدخول</Link>
            </div>
        </div>

        <div v-if="hasUsedIdentifierError" class="mb-4 rounded-lg border border-brand-100 bg-brand-bg px-4 py-3 text-sm text-brand-800">
            يبدو أن هذا البريد/الرقم مستخدم مسبقًا. يمكنك تسجيل الدخول مباشرة.
            <div class="mt-2">
                <Link :href="route('login')" class="font-bold underline">الانتقال إلى تسجيل الدخول</Link>
            </div>
        </div>

        <form novalidate @submit.prevent="submit" dir="rtl">
            <div>
                <InputLabel for="name" value="الاسم *" />
                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" autofocus autocomplete="name" @blur="touched.name = true" />
                <InputError class="mt-2" :message="form.errors.name || clientErrors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="البريد الإلكتروني أو رقم الجوال *" />
                <TextInput id="email" type="text" class="mt-1 block w-full" v-model="form.email" autocomplete="username" @blur="touched.email = true" />
                <InputError class="mt-2" :message="form.errors.email || clientErrors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="كلمة المرور *" />
                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" autocomplete="new-password" @blur="touched.password = true" />
                <InputError class="mt-2" :message="form.errors.password || clientErrors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="تأكيد كلمة المرور *" />
                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" autocomplete="new-password" @blur="touched.password_confirmation = true" />
                <InputError class="mt-2" :message="form.errors.password_confirmation || clientErrors.password_confirmation" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link :href="route('login')" class="text-sm text-brand-700 underline">
                    لدي حساب بالفعل
                </Link>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-700" @mousedown.prevent.stop="goHome" @click.prevent.stop="goHome">
                        إلغاء
                    </button>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        تسجيل
                    </PrimaryButton>
                </div>
            </div>
        </form>

        <Modal :show="showRegisteringModal" max-width="md" :closeable="false">
            <div class="p-6" dir="rtl">
                <h3 class="text-lg font-bold text-brand-900">جاري إنشاء الحساب</h3>
                <p class="mt-2 text-sm text-brand-700">يرجى الانتظار...</p>
                <div class="mt-5 flex justify-center">
                    <span class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600"></span>
                </div>
            </div>
        </Modal>
    </GuestLayout>
</template>
