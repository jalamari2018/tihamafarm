<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    myFarms: {
        type: Array,
        default: () => [],
    },
    myHarvests: {
        type: Array,
        default: () => [],
    },
    myEquipment: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('farm');
const localError = ref('');
const deleteTarget = ref(null);

const farmForm = useForm({
    farm_name: '',
    farmer_name: '',
    phone: '',
    location_text: '',
    length: '',
    width: '',
    description: '',
    image: null,
});

const harvestForm = useForm({
    harvest_name: '',
    farmer_name: '',
    phone: '',
    location_text: '',
    ready_status: 'now',
    ready_date: '',
    description: '',
    image: null,
});

const equipmentForm = useForm({
    product_name: '',
    seller_name: '',
    phone: '',
    location_text: '',
    price: '',
    description: '',
    image: null,
});

const farmArea = computed(() => {
    const l = Number(farmForm.length);
    const w = Number(farmForm.width);

    if (!l || !w || l <= 0 || w <= 0) {
        return null;
    }

    return (l * w).toFixed(2);
});

const farmClientErrors = computed(() => ({
    farm_name: farmForm.farm_name ? '' : 'اسم المزرعة مطلوب.',
    farmer_name: farmForm.farmer_name ? '' : 'اسم المزارع مطلوب.',
    phone: farmForm.phone ? '' : 'رقم الهاتف مطلوب.',
    location_text: farmForm.location_text ? '' : 'الموقع مطلوب.',
    length: Number(farmForm.length) > 0 ? '' : 'الطول يجب أن يكون أكبر من صفر.',
    width: Number(farmForm.width) > 0 ? '' : 'العرض يجب أن يكون أكبر من صفر.',
    image: farmForm.image ? '' : 'الصورة مطلوبة.',
}));

const harvestClientErrors = computed(() => ({
    harvest_name: harvestForm.harvest_name ? '' : 'اسم المحصول مطلوب.',
    farmer_name: harvestForm.farmer_name ? '' : 'اسم المزارع مطلوب.',
    phone: harvestForm.phone ? '' : 'رقم الهاتف مطلوب.',
    location_text: harvestForm.location_text ? '' : 'الموقع مطلوب.',
    ready_date:
        harvestForm.ready_status === 'future' && !harvestForm.ready_date
            ? 'حدد تاريخ الجاهزية.'
            : '',
    image: harvestForm.image ? '' : 'الصورة مطلوبة.',
}));

const equipmentClientErrors = computed(() => ({
    product_name: equipmentForm.product_name ? '' : 'اسم المنتج مطلوب.',
    seller_name: equipmentForm.seller_name ? '' : 'اسم البائع مطلوب.',
    phone: equipmentForm.phone ? '' : 'رقم الهاتف مطلوب.',
    location_text: equipmentForm.location_text ? '' : 'الموقع مطلوب.',
    price: Number(equipmentForm.price) >= 0 && equipmentForm.price !== '' ? '' : 'السعر مطلوب.',
    image: equipmentForm.image ? '' : 'الصورة مطلوبة.',
}));

const hasErrors = (errors) => Object.values(errors).some((value) => value);

const submitFarm = () => {
    localError.value = '';

    if (hasErrors(farmClientErrors.value)) {
        localError.value = 'يرجى تصحيح أخطاء نموذج المزرعة.';
        return;
    }

    farmForm.post(route('farms.store'), {
        forceFormData: true,
        onSuccess: () => {
            farmForm.reset();
        },
    });
};

const submitHarvest = () => {
    localError.value = '';

    if (hasErrors(harvestClientErrors.value)) {
        localError.value = 'يرجى تصحيح أخطاء نموذج المحصول.';
        return;
    }

    harvestForm.post(route('harvests.store'), {
        forceFormData: true,
        onSuccess: () => {
            harvestForm.reset();
            harvestForm.ready_status = 'now';
        },
    });
};

const submitEquipment = () => {
    localError.value = '';

    if (hasErrors(equipmentClientErrors.value)) {
        localError.value = 'يرجى تصحيح أخطاء نموذج المعدات.';
        return;
    }

    equipmentForm.post(route('equipment.store'), {
        forceFormData: true,
        onSuccess: () => {
            equipmentForm.reset();
        },
    });
};

const openDeleteModal = (type, id, name) => {
    deleteTarget.value = { type, id, name };
};

const closeDeleteModal = () => {
    deleteTarget.value = null;
};

const confirmDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    const routeName = {
        farm: 'farms.destroy',
        harvest: 'harvests.destroy',
        equipment: 'equipment.destroy',
    }[deleteTarget.value.type];

    router.delete(route(routeName, deleteTarget.value.id), {
        onFinish: () => {
            closeDeleteModal();
        },
    });
};
</script>

<template>
    <Head title="لوحة التحكم" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-extrabold text-brand-900">لوحة التحكم</h1>
            <p class="mt-1 text-sm text-brand-700">أنشئ إعلاناتك وأدرجها مباشرة في الموقع.</p>
        </template>

        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <div v-if="localError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ localError }}
            </div>

            <section class="rounded-3xl border border-brand-100 bg-white p-5 shadow-lg">
                <div class="mb-4 flex flex-wrap gap-2">
                    <button
                        class="rounded-xl px-4 py-2 text-sm font-bold"
                        :class="activeTab === 'farm' ? 'bg-brand-700 text-white' : 'bg-brand-bg text-brand-700'"
                        @click="activeTab = 'farm'"
                    >
                        إضافة مزرعة
                    </button>
                    <button
                        class="rounded-xl px-4 py-2 text-sm font-bold"
                        :class="activeTab === 'harvest' ? 'bg-brand-700 text-white' : 'bg-brand-bg text-brand-700'"
                        @click="activeTab = 'harvest'"
                    >
                        إضافة محصول
                    </button>
                    <button
                        class="rounded-xl px-4 py-2 text-sm font-bold"
                        :class="activeTab === 'equipment' ? 'bg-brand-700 text-white' : 'bg-brand-bg text-brand-700'"
                        @click="activeTab = 'equipment'"
                    >
                        إضافة معدات
                    </button>
                </div>

                <form v-if="activeTab === 'farm'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitFarm">
                    <div>
                        <label class="text-sm font-semibold">اسم المزرعة</label>
                        <input v-model="farmForm.farm_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.farm_name || farmClientErrors.farm_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">اسم المزارع</label>
                        <input v-model="farmForm.farmer_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.farmer_name || farmClientErrors.farmer_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">رقم الهاتف</label>
                        <input v-model="farmForm.phone" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.phone || farmClientErrors.phone" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">الموقع (وصف نصي)</label>
                        <input v-model="farmForm.location_text" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.location_text || farmClientErrors.location_text" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">الطول</label>
                        <input v-model="farmForm.length" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.length || farmClientErrors.length" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">العرض</label>
                        <input v-model="farmForm.width" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="farmForm.errors.width || farmClientErrors.width" class="mt-1" />
                    </div>
                    <div class="md:col-span-2 rounded-lg bg-brand-bg p-3 text-sm font-bold text-brand-900">
                        المساحة المحسوبة: {{ farmArea ? `${farmArea} م²` : 'أدخل الطول والعرض' }}
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">الوصف</label>
                        <textarea v-model="farmForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea>
                        <InputError :message="farmForm.errors.description" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">صورة المزرعة</label>
                        <input type="file" accept="image/*" class="mt-1 w-full" @change="farmForm.image = $event.target.files[0]" />
                        <InputError :message="farmForm.errors.image || farmClientErrors.image" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2 font-bold text-white" :disabled="farmForm.processing">
                            نشر إعلان المزرعة
                        </button>
                    </div>
                </form>

                <form v-if="activeTab === 'harvest'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitHarvest">
                    <div>
                        <label class="text-sm font-semibold">اسم المحصول</label>
                        <input v-model="harvestForm.harvest_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="harvestForm.errors.harvest_name || harvestClientErrors.harvest_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">اسم المزارع</label>
                        <input v-model="harvestForm.farmer_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="harvestForm.errors.farmer_name || harvestClientErrors.farmer_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">رقم الهاتف</label>
                        <input v-model="harvestForm.phone" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="harvestForm.errors.phone || harvestClientErrors.phone" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">الموقع (وصف نصي)</label>
                        <input v-model="harvestForm.location_text" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="harvestForm.errors.location_text || harvestClientErrors.location_text" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">حالة الجاهزية</label>
                        <div class="mt-2 flex gap-6 text-sm">
                            <label class="flex items-center gap-2">
                                <input v-model="harvestForm.ready_status" type="radio" value="now" />
                                جاهز الآن
                            </label>
                            <label class="flex items-center gap-2">
                                <input v-model="harvestForm.ready_status" type="radio" value="future" />
                                جاهز في تاريخ لاحق
                            </label>
                        </div>
                    </div>
                    <div v-if="harvestForm.ready_status === 'future'" class="md:col-span-2">
                        <label class="text-sm font-semibold">تاريخ الجاهزية</label>
                        <input v-model="harvestForm.ready_date" type="date" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="harvestForm.errors.ready_date || harvestClientErrors.ready_date" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">الوصف</label>
                        <textarea v-model="harvestForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea>
                        <InputError :message="harvestForm.errors.description" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">صورة المحصول</label>
                        <input type="file" accept="image/*" class="mt-1 w-full" @change="harvestForm.image = $event.target.files[0]" />
                        <InputError :message="harvestForm.errors.image || harvestClientErrors.image" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2 font-bold text-white" :disabled="harvestForm.processing">
                            نشر إعلان المحصول
                        </button>
                    </div>
                </form>

                <form v-if="activeTab === 'equipment'" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitEquipment">
                    <div>
                        <label class="text-sm font-semibold">اسم المنتج</label>
                        <input v-model="equipmentForm.product_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="equipmentForm.errors.product_name || equipmentClientErrors.product_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">اسم البائع</label>
                        <input v-model="equipmentForm.seller_name" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="equipmentForm.errors.seller_name || equipmentClientErrors.seller_name" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">رقم الهاتف</label>
                        <input v-model="equipmentForm.phone" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="equipmentForm.errors.phone || equipmentClientErrors.phone" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">الموقع (وصف نصي)</label>
                        <input v-model="equipmentForm.location_text" type="text" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="equipmentForm.errors.location_text || equipmentClientErrors.location_text" class="mt-1" />
                    </div>
                    <div>
                        <label class="text-sm font-semibold">السعر</label>
                        <input v-model="equipmentForm.price" type="number" step="0.01" class="mt-1 w-full rounded-lg border-brand-100" />
                        <InputError :message="equipmentForm.errors.price || equipmentClientErrors.price" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">الوصف</label>
                        <textarea v-model="equipmentForm.description" class="mt-1 w-full rounded-lg border-brand-100"></textarea>
                        <InputError :message="equipmentForm.errors.description" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold">صورة المنتج</label>
                        <input type="file" accept="image/*" class="mt-1 w-full" @change="equipmentForm.image = $event.target.files[0]" />
                        <InputError :message="equipmentForm.errors.image || equipmentClientErrors.image" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-xl bg-brand-900 px-5 py-2 font-bold text-white" :disabled="equipmentForm.processing">
                            نشر إعلان المعدات
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-brand-100 bg-white p-5 shadow-lg">
                <h2 class="mb-4 text-xl font-bold text-brand-900">إعلاناتي</h2>
                <div class="grid gap-6 lg:grid-cols-3">
                    <div>
                        <h3 class="mb-3 text-lg font-bold text-brand-700">المزارع</h3>
                        <div class="space-y-3">
                            <article v-for="farm in myFarms" :key="`my-farm-${farm.id}`" class="rounded-xl border border-brand-100 p-3 text-sm">
                                <img :src="farm.image_url" alt="farm" class="mb-2 h-28 w-full rounded-lg object-cover" />
                                <p class="font-bold">{{ farm.farm_name }}</p>
                                <p>{{ farm.location_text }}</p>
                                <p>المساحة: {{ farm.area }} م²</p>
                                <button class="mt-2 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="openDeleteModal('farm', farm.id, farm.farm_name)">
                                    حذف
                                </button>
                            </article>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-lg font-bold text-brand-700">المحاصيل</h3>
                        <div class="space-y-3">
                            <article v-for="harvest in myHarvests" :key="`my-harvest-${harvest.id}`" class="rounded-xl border border-brand-100 p-3 text-sm">
                                <img :src="harvest.image_url" alt="harvest" class="mb-2 h-28 w-full rounded-lg object-cover" />
                                <p class="font-bold">{{ harvest.harvest_name }}</p>
                                <p>{{ harvest.location_text }}</p>
                                <p v-if="harvest.ready_status === 'future'">جاهز خلال {{ harvest.ready_in_days }} يوم</p>
                                <p v-else>جاهز الآن</p>
                                <button class="mt-2 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="openDeleteModal('harvest', harvest.id, harvest.harvest_name)">
                                    حذف
                                </button>
                            </article>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-lg font-bold text-brand-700">المعدات</h3>
                        <div class="space-y-3">
                            <article v-for="item in myEquipment" :key="`my-equipment-${item.id}`" class="rounded-xl border border-brand-100 p-3 text-sm">
                                <img :src="item.image_url" alt="equipment" class="mb-2 h-28 w-full rounded-lg object-cover" />
                                <p class="font-bold">{{ item.product_name }}</p>
                                <p>{{ item.location_text }}</p>
                                <p>السعر: {{ item.price }}</p>
                                <button class="mt-2 rounded-lg bg-red-600 px-3 py-1 text-xs font-bold text-white" @click="openDeleteModal('equipment', item.id, item.product_name)">
                                    حذف
                                </button>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Modal :show="!!deleteTarget" max-width="lg" @close="closeDeleteModal">
            <div class="p-6" dir="rtl">
                <h3 class="text-lg font-bold text-brand-900">تأكيد الحذف</h3>
                <p class="mt-2 text-sm text-gray-600">
                    هل أنت متأكد من حذف إعلان <strong>{{ deleteTarget?.name }}</strong>؟ لا يمكن التراجع.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button class="rounded-lg border border-brand-100 px-4 py-2 text-sm" @click="closeDeleteModal">إلغاء</button>
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white" @click="confirmDelete">تأكيد الحذف</button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
