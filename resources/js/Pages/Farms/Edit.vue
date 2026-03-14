<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ farm: { type: Object, required: true } });
const page = usePage();
const showSuccessModal = ref(Boolean(page.props.flash?.success_modal));

const form = useForm({
    farm_name: props.farm.farm_name,
    farmer_name: props.farm.farmer_name,
    phone: props.farm.phone,
    location_text: props.farm.location_text,
    length: props.farm.length,
    width: props.farm.width,
    has_well: !!props.farm.has_well,
    has_electricity: !!props.farm.has_electricity,
    description: props.farm.description ?? '',
    image: null,
});

const submit = () => {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('farms.update', props.farm.id), {
            forceFormData: true,
        });
};

const goToCategoryList = () => {
    showSuccessModal.value = false;
    router.get(route('dashboard', { panel: 'farms' }));
};
</script>

<template>
    <Head title="تعديل المزرعة" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-extrabold text-brand-900">تعديل إعلان المزرعة</h1>
        </template>

        <div class="mx-auto max-w-3xl rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
            <img :src="farm.image_url" alt="farm" class="mb-4 h-48 w-full rounded-lg object-cover" />
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div><label>اسم المزرعة</label><input v-model="form.farm_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.farm_name" /></div>
                <div><label>اسم المزارع</label><input v-model="form.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.farmer_name" /></div>
                <div><label>رقم الهاتف</label><input v-model="form.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.phone" /></div>
                <div><label>الموقع</label><input v-model="form.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.location_text" /></div>
                <div><label>الطول</label><input v-model="form.length" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.length" /></div>
                <div><label>العرض</label><input v-model="form.width" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.width" /></div>
                <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-brand-bg px-3 py-2">
                        <span class="text-sm font-semibold text-brand-900">يوجد بئر</span>
                        <span class="relative inline-flex h-6 w-11 items-center">
                            <input v-model="form.has_well" type="checkbox" class="peer sr-only" />
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                            <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                        </span>
                    </label>
                    <label class="flex items-center justify-between rounded-lg border border-brand-100 bg-brand-bg px-3 py-2">
                        <span class="text-sm font-semibold text-brand-900">توجد كهرباء</span>
                        <span class="relative inline-flex h-6 w-11 items-center">
                            <input v-model="form.has_electricity" type="checkbox" class="peer sr-only" />
                            <span class="absolute inset-0 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600"></span>
                            <span class="absolute right-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-[-20px]"></span>
                        </span>
                    </label>
                </div>
                <div class="md:col-span-2"><label>الوصف</label><textarea v-model="form.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea><InputError :message="form.errors.description" /></div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold">هل تريد تغيير الصورة ؟</label>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <label class="cursor-pointer rounded-lg bg-brand-100 px-4 py-2 text-sm font-bold text-brand-900 hover:bg-brand-200">
                            اختر صورة
                            <input type="file" accept="image/*" class="hidden" @change="form.image = $event.target.files[0]" />
                        </label>
                        <span class="text-sm text-brand-700">{{ form.image ? form.image.name : 'لم يتم اختيار ملف' }}</span>
                    </div>
                    <InputError :message="form.errors.image" />
                </div>
                <div class="md:col-span-2 flex justify-end gap-2">
                    <a :href="route('dashboard')" class="rounded-lg border border-brand-100 px-4 py-2">رجوع</a>
                    <button class="rounded-lg bg-brand-900 px-4 py-2 font-bold text-white" :disabled="form.processing">حفظ التعديلات</button>
                </div>
            </form>
        </div>

        <Modal :show="showSuccessModal" max-width="md" @close="showSuccessModal = false">
            <div class="p-6" dir="rtl">
                <h3 class="text-lg font-bold text-brand-900">تم التحديث بنجاح</h3>
                <p class="mt-2 text-sm text-brand-700">{{ page.props.flash?.success_modal }}</p>
                <div class="mt-5 flex justify-end">
                    <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" @click="goToCategoryList">عرض قائمة المزارع</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
