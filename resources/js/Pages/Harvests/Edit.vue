<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ harvest: { type: Object, required: true } });
const page = usePage();
const showSuccessModal = ref(Boolean(page.props.flash?.success_modal));

const form = useForm({
    harvest_name: props.harvest.harvest_name,
    farmer_name: props.harvest.farmer_name,
    phone: props.harvest.phone,
    location_text: props.harvest.location_text,
    ready_status: props.harvest.ready_status,
    ready_date: props.harvest.ready_date ?? '',
    description: props.harvest.description ?? '',
    image: null,
});

const submit = () => {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('harvests.update', props.harvest.id), {
            forceFormData: true,
        });
};

const goToCategoryList = () => {
    showSuccessModal.value = false;
    router.get(route('dashboard', { panel: 'harvests' }));
};
</script>

<template>
    <Head title="تعديل المحصول" />
    <AuthenticatedLayout>
        <template #header><h1 class="text-2xl font-extrabold text-brand-900">تعديل إعلان المحصول</h1></template>
        <div class="mx-auto max-w-3xl rounded-2xl border border-brand-100 bg-white p-6 shadow-sm">
            <img :src="harvest.image_url" alt="harvest" class="mb-4 h-48 w-full rounded-lg object-cover" />
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div><label>اسم المحصول</label><input v-model="form.harvest_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.harvest_name" /></div>
                <div><label>اسم المزارع</label><input v-model="form.farmer_name" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.farmer_name" /></div>
                <div><label>رقم الهاتف</label><input v-model="form.phone" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.phone" /></div>
                <div><label>الموقع</label><input v-model="form.location_text" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.location_text" /></div>
                <div class="md:col-span-2 flex gap-6 text-sm">
                    <label class="flex items-center gap-2"><input v-model="form.ready_status" type="radio" value="now" />جاهز الآن</label>
                    <label class="flex items-center gap-2"><input v-model="form.ready_status" type="radio" value="future" />جاهز لاحقًا</label>
                </div>
                <div v-if="form.ready_status === 'future'" class="md:col-span-2"><label>تاريخ الجاهزية</label><input v-model="form.ready_date" type="date" lang="ar" dir="rtl" class="mt-1 w-full rounded-lg border-brand-100" /><InputError :message="form.errors.ready_date" /></div>
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
                <div class="md:col-span-2 flex justify-end gap-2"><a :href="route('dashboard')" class="rounded-lg border border-brand-100 px-4 py-2">رجوع</a><button class="rounded-lg bg-brand-900 px-4 py-2 font-bold text-white" :disabled="form.processing">حفظ التعديلات</button></div>
            </form>
        </div>

        <Modal :show="showSuccessModal" max-width="md" @close="showSuccessModal = false">
            <div class="p-6" dir="rtl">
                <h3 class="text-lg font-bold text-brand-900">تم التحديث بنجاح</h3>
                <p class="mt-2 text-sm text-brand-700">{{ page.props.flash?.success_modal }}</p>
                <div class="mt-5 flex justify-end">
                    <button class="rounded-lg bg-brand-900 px-4 py-2 text-sm font-bold text-white" @click="goToCategoryList">عرض قائمة المحاصيل</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
