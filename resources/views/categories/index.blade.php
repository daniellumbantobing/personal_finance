<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        activeTab: 'all',
        showModal: false,
        isEdit: false,
        formAction: '{{ route('categories.store') }}',
        name: '',
        type: 'expense',
        icon: 'label',
        openCreate() {
            this.isEdit = false;
            this.formAction = '{{ route('categories.store') }}';
            this.name = '';
            this.type = 'expense';
            this.icon = 'label';
            this.showModal = true;
        },
        openEdit(cat) {
            this.isEdit = true;
            this.formAction = '/categories/' + cat.id;
            this.name = cat.name;
            this.type = cat.type;
            this.icon = cat.icon || 'label';
            this.showModal = true;
        }
    }">
        <!-- Header Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-2">
                    <span class="material-symbols-outlined text-[15px]">category</span>
                    <span>Pengelompokan Keuangan</span>
                </div>
                <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Kategori Transaksi
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    Kelompokkan pemasukan dan pengeluaran Anda untuk analisis visual yang presisi.
                </p>
            </div>

            <button @click="openCreate()" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-2 self-start sm:self-auto">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                <span>Tambah Kategori</span>
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 w-fit">
            <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition-all">
                Semua ({{ $categories->count() }})
            </button>
            <button @click="activeTab = 'expense'" :class="activeTab === 'expense' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Pengeluaran ({{ $expenseCategories->count() }})</span>
            </button>
            <button @click="activeTab = 'income'" :class="activeTab === 'income' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-bold shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Pemasukan ({{ $incomeCategories->count() }})</span>
            </button>
        </div>

        <!-- Categories Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @forelse ($categories as $category)
                <div x-show="activeTab === 'all' || activeTab === '{{ $category->type }}'" class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-card hover:shadow-float transition-all flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0 {{ $category->type === 'income' ? 'bg-emerald-500 shadow-sm shadow-emerald-500/20' : 'bg-rose-500 shadow-sm shadow-rose-500/20' }}">
                            <span class="material-symbols-outlined text-[20px]">{{ $category->icon ?? 'label' }}</span>
                        </div>
                        <div class="truncate">
                            <h4 class="font-display font-bold text-sm text-slate-900 dark:text-white truncate">{{ $category->name }}</h4>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] font-semibold uppercase tracking-wider {{ $category->type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                </span>
                                @if($category->is_default)
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-medium">Bawaan</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(!$category->is_default && $category->user_id === auth()->id())
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="openEdit({{ $category->toJson() }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800" title="Ubah">
                                <span class="material-symbols-outlined text-[16px]">edit</span>
                            </button>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800" title="Hapus">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-card">
                    <span class="material-symbols-outlined text-4xl text-slate-300 dark:text-slate-600 mb-2">category</span>
                    <h3 class="font-display font-bold text-base text-slate-800 dark:text-slate-200">Belum Ada Kategori</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tambahkan kategori untuk memudahkan pemantauan anggaran.</p>
                </div>
            @endforelse
        </div>

        <!-- Modal Create / Edit Category -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition.opacity @click="showModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                <span class="material-symbols-outlined text-[20px]">category</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" x-text="isEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru'"></h3>
                                <p class="text-xs text-slate-500">Sesuaikan nama dan jenis kategori Anda.</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <form :action="formAction" method="POST" class="space-y-4">
                        @csrf
                        <template x-if="isEdit">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Kategori <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" x-model="name" required placeholder="Contoh: Langganan SaaS, Kopi, Kado" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kategori <span class="text-rose-500">*</span></label>
                            <select name="type" x-model="type" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="expense">Pengeluaran (Expense)</option>
                                <option value="income">Pemasukan (Income)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Ikon Material (Opsional)</label>
                            <input type="text" name="icon" x-model="icon" placeholder="shopping_cart, restaurant, flight, work, dll." class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="text-[10px] text-slate-400 mt-1">Gunakan nama ikon dari Google Material Symbols.</p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95">
                                Simpan Kategori
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

