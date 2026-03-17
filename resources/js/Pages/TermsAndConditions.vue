<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <GuestLayout>
        <Head title="الشروط والأحكام" />

        <div class="space-y-6" dir="rtl">
            <div>
                <h1 class="text-2xl font-extrabold text-brand-900">الشروط والأحكام</h1>
                <p class="mt-2 text-sm leading-7 text-brand-700">
                    باستخدامك منصة تهامة فارم، فأنت توافق على هذه الشروط. الهدف من المنصة هو ربط المزارعين بالمستثمرين بطريقة آمنة ومحترمة ومفيدة للمجتمع.
                </p>
            </div>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">1. التحويلات المالية وإخلاء المسؤولية</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    المنصة غير مسؤولة عن أي تحويل مالي يتم بين المستخدمين. جميع المعاملات المالية تقع على مسؤولية أطرافها بشكل كامل، ويجب التأكد من هوية الطرف الآخر وصحة الاتفاق قبل أي تحويل.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">2. السلوك والاحترام المتبادل</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    المنصة مجتمعية لتمكين التعاون بين المزارعين والمستثمرين. يجب التعامل باحترام مع جميع الأعضاء، ويُمنع الإساءة أو التهديد أو أي محتوى مهين أو مضلل.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">3. دقة المعلومات</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    أنت مسؤول عن صحة المعلومات التي تضيفها في الإعلانات أو الملفات الشخصية. قد يتم حذف المحتوى غير الصحيح أو المخالف دون إشعار مسبق.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">4. الاستخدام المقبول</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    يُمنع استخدام المنصة لأي نشاط غير قانوني، أو محاولة الوصول غير المصرح به، أو نشر برمجيات ضارة، أو استغلال المنصة بطريقة تضر بالمستخدمين أو بالخدمة.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">5. حسابات المستخدمين</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    أنت مسؤول عن حماية بيانات دخولك وعن أي نشاط يتم من خلال حسابك. يحق للمنصة تعليق أو حذف أي حساب يخالف هذه الشروط أو يسيء استخدام الخدمة.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">6. الملكية الفكرية والمحتوى</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    يحتفظ كل مستخدم بحقوق المحتوى الذي يرفعه، مع منحه المنصة حق عرضه ضمن الخدمة. لا يجوز نسخ أو إعادة استخدام محتوى الغير بدون إذن.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">7. تعديل الشروط</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    قد يتم تحديث هذه الشروط عند الحاجة. استمرارك في استخدام المنصة بعد نشر التحديثات يعني موافقتك على النسخة الجديدة.
                </p>
            </section>

            <section class="rounded-xl border border-brand-100 bg-brand-bg/70 p-4">
                <h2 class="text-base font-bold text-brand-900">8. القانون المعمول به</h2>
                <p class="mt-2 text-sm leading-7 text-brand-800">
                    تخضع هذه الشروط للأنظمة المعمول بها في المملكة العربية السعودية.
                </p>
            </section>

            <div class="flex justify-end">
                <Link :href="route('register')" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-extrabold text-white shadow-lg shadow-emerald-600/30 transition hover:bg-emerald-500">
                    العودة إلى التسجيل
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
