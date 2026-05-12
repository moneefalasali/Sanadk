<section class="bg-gradient-to-br from-slate-50 to-slate-100 p-6 rounded-3xl border border-slate-200 shadow-xl shadow-slate-300/40 space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            حذف الحساب
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            بعد حذف حسابك، ستُحذف جميع الموارد والبيانات المرتبطة به نهائياً. يرجى تنزيل أي بيانات ترغب بالاحتفاظ بها قبل الحذف.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 shadow-lg shadow-rose-500/20"
    >حذف الحساب</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                هل أنت متأكد من حذف حسابك؟
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                بعد حذف الحساب، ستُحذف جميع البيانات والموارد نهائياً. الرجاء إدخال كلمة المرور للتأكيد.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="كلمة المرور" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="كلمة المرور"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="bg-slate-200 text-slate-900 hover:bg-slate-300 border-slate-300">
                    إلغاء
                </x-secondary-button>

                <x-danger-button class="ms-3 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 shadow-lg shadow-rose-500/20">
                    حذف الحساب
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
