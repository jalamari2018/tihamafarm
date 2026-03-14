<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const touched = ref({
    email: false,
    password: false,
});
const attemptedSubmit = ref(false);

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
    email: shouldShow('email')
        ? (form.email.trim()
            ? (isEmail(form.email.trim()) || isSaudiPhone(form.email.trim()) ? '' : 'أدخل بريدًا إلكترونيًا صحيحًا أو رقم جوال سعودي بصيغة 05XXXXXXXX.')
            : 'البريد الإلكتروني أو رقم الجوال مطلوب.')
        : '',
    password: shouldShow('password') ? (form.password ? '' : 'كلمة المرور مطلوبة.') : '',
}));

const hasClientErrors = computed(() => Object.values(clientErrors.value).some(Boolean));

const submit = () => {
    attemptedSubmit.value = true;

    if (hasClientErrors.value) {
        return;
    }

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const goHome = () => {
    router.get(route('home'));
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول" />

        <h1 class="mb-4 text-xl font-bold text-brand-900">تسجيل الدخول</h1>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form novalidate @submit.prevent="submit" dir="rtl">
            <div>
                <InputLabel for="email" value="البريد الإلكتروني أو رقم الجوال *" />
                <TextInput id="email" type="text" class="mt-1 block w-full" v-model="form.email" autofocus autocomplete="username" @blur="touched.email = true" />
                <InputError class="mt-2" :message="form.errors.email || clientErrors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="كلمة المرور *" />
                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" autocomplete="current-password" @blur="touched.password = true" />
                <InputError class="mt-2" :message="form.errors.password || clientErrors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">تذكرني</span>
                </label>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-brand-700 underline">
                    نسيت كلمة المرور؟
                </Link>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-brand-700" @mousedown.prevent.stop="goHome" @click.prevent.stop="goHome">
                        إلغاء
                    </button>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        دخول
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
