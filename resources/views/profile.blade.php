<x-app-layout>
    <div class="space-y-8 pb-12" x-data="{
        showDeleteModal: false,
        deletePassword: '',
        showCurrentPw: false,
        showNewPw: false,
        showConfirmPw: false
    }">
        <!-- Header Banner & Hero Card -->
        <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-card border border-indigo-800/40">
            <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -top-10 w-60 h-60 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4 sm:gap-6">
                    <!-- User Avatar Initial -->
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-indigo-500 via-indigo-400 to-purple-500 p-[3px] shadow-lg shadow-indigo-950/50 flex-shrink-0">
                        <div class="w-full h-full rounded-2xl bg-slate-900/90 backdrop-blur-sm flex items-center justify-center font-display font-black text-2xl sm:text-3xl text-indigo-300">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-bold border border-emerald-500/30">
                                <span class="material-symbols-outlined text-[14px]">verified</span>
                                <span>Akun FinAI Aktif</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/10 text-slate-300 text-[11px] font-medium border border-white/10">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                <span>{{ $user->timezone ?? 'Asia/Jakarta' }}</span>
                            </span>
                        </div>
                        <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-white tracking-tight">
                            {{ $user->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-0.5 flex items-center gap-2">
                            <span>{{ $user->email }}</span>
                            <span class="text-slate-500">•</span>
                            <span>Bergabung sejak {{ $user->created_at ? $user->created_at->format('d M Y') : 'Hari ini' }}</span>
                        </p>
                    </div>
                </div>

                <!-- Fast Actions -->
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold backdrop-blur-sm border border-white/10 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">dashboard</span>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('accounts.index') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-900/40 transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">account_balance_wallet</span>
                        <span>Dompet</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Bento Financial Stats Counter -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined text-[18px]">account_balance</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Dompet Terhubung</p>
                <p class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white mt-0.5">{{ $stats['accounts_count'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Transaksi</p>
                <p class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white mt-0.5">{{ $stats['transactions_count'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined text-[18px]">savings</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Target Tabungan</p>
                <p class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white mt-0.5">{{ $stats['saving_goals_count'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-card">
                <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined text-[18px]">pie_chart</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Batas Anggaran</p>
                <p class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white mt-0.5">{{ $stats['budgets_count'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-card col-span-2 sm:col-span-1">
                <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-2">
                    <span class="material-symbols-outlined text-[18px]">handshake</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Catatan Hutang/Piutang</p>
                <p class="font-display font-black text-lg sm:text-xl text-slate-900 dark:text-white mt-0.5">{{ $stats['debts_count'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Main Profile Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
            <!-- 1. INFORMASI PROFIL & REGIONAL -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center gap-3 pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[22px]">manage_accounts</span>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Informasi Profil</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Ubah detail identitas dan konfigurasi mata uang utama</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-2xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">person</span>
                            </div>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" placeholder="Nama Lengkap Anda" class="w-full pl-10 pr-4 py-2.5 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                        </div>
                        @error('name')
                            <p class="text-rose-500 text-xs font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Alamat Email <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-2xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">mail</span>
                            </div>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" placeholder="nama@email.com" class="w-full pl-10 pr-4 py-2.5 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                        </div>
                        @error('email')
                            <p class="text-rose-500 text-xs font-medium mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mata Uang & Zona Waktu -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="currency" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Mata Uang Utama</label>
                            <div class="relative rounded-2xl">
                                <select id="currency" name="currency" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium">
                                    <option value="IDR" {{ old('currency', $user->currency) == 'IDR' ? 'selected' : '' }}>IDR - Rupiah (Rp)</option>
                                    <option value="USD" {{ old('currency', $user->currency) == 'USD' ? 'selected' : '' }}>USD - Dollar ($)</option>
                                    <option value="EUR" {{ old('currency', $user->currency) == 'EUR' ? 'selected' : '' }}>EUR - Euro (€)</option>
                                    <option value="SGD" {{ old('currency', $user->currency) == 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar (S$)</option>
                                    <option value="MYR" {{ old('currency', $user->currency) == 'MYR' ? 'selected' : '' }}>MYR - Ringgit (RM)</option>
                                    <option value="JPY" {{ old('currency', $user->currency) == 'JPY' ? 'selected' : '' }}>JPY - Yen (¥)</option>
                                </select>
                            </div>
                            @error('currency')
                                <p class="text-rose-500 text-xs font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="timezone" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Zona Waktu</label>
                            <div class="relative rounded-2xl">
                                <select id="timezone" name="timezone" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium">
                                    <option value="Asia/Jakarta" {{ old('timezone', $user->timezone) == 'Asia/Jakarta' ? 'selected' : '' }}>Asia/Jakarta (WIB)</option>
                                    <option value="Asia/Makassar" {{ old('timezone', $user->timezone) == 'Asia/Makassar' ? 'selected' : '' }}>Asia/Makassar (WITA)</option>
                                    <option value="Asia/Jayapura" {{ old('timezone', $user->timezone) == 'Asia/Jayapura' ? 'selected' : '' }}>Asia/Jayapura (WIT)</option>
                                    <option value="UTC" {{ old('timezone', $user->timezone) == 'UTC' ? 'selected' : '' }}>UTC / GMT</option>
                                </select>
                            </div>
                            @error('timezone')
                                <p class="text-rose-500 text-xs font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 active:scale-95 transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">save</span>
                            <span>Simpan Informasi Profil</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. GANTI KATA SANDI & KEAMANAN -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-card">
                <div class="flex items-center gap-3 pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
                    <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-[22px]">lock_reset</span>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Keamanan &amp; Kata Sandi</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Kata Sandi Saat Ini -->
                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kata Sandi Saat Ini <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-2xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">key</span>
                            </div>
                            <input :type="showCurrentPw ? 'text' : 'password'" id="current_password" name="current_password" required autocomplete="current-password" placeholder="••••••••" class="w-full pl-10 pr-10 py-2.5 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                            <button type="button" @click="showCurrentPw = !showCurrentPw" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-symbols-outlined text-[18px]" x-text="showCurrentPw ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        @if ($errors->updatePassword->has('current_password'))
                            <p class="text-rose-500 text-xs font-medium mt-1">{{ $errors->updatePassword->first('current_password') }}</p>
                        @endif
                    </div>

                    <!-- Kata Sandi Baru -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kata Sandi Baru <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-2xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">lock</span>
                            </div>
                            <input :type="showNewPw ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="w-full pl-10 pr-10 py-2.5 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                            <button type="button" @click="showNewPw = !showNewPw" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-symbols-outlined text-[18px]" x-text="showNewPw ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        @if ($errors->updatePassword->has('password'))
                            <p class="text-rose-500 text-xs font-medium mt-1">{{ $errors->updatePassword->first('password') }}</p>
                        @endif
                    </div>

                    <!-- Konfirmasi Kata Sandi Baru -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span></label>
                        <div class="relative rounded-2xl">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-[18px] text-slate-400">check_circle</span>
                            </div>
                            <input :type="showConfirmPw ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi baru" class="w-full pl-10 pr-10 py-2.5 rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                            <button type="button" @click="showConfirmPw = !showConfirmPw" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <span class="material-symbols-outlined text-[18px]" x-text="showConfirmPw ? 'visibility_off' : 'visibility'"></span>
                            </button>
                        </div>
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <p class="text-rose-500 text-xs font-medium mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</p>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-700 text-white text-xs font-bold shadow-md active:scale-95 transition-all flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">shield_lock</span>
                            <span>Perbarui Kata Sandi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. DANGER ZONE: HAPUS AKUN -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-rose-200/80 dark:border-rose-950/80 shadow-card">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="material-symbols-outlined text-[22px]">warning</span>
                    </div>
                    <div>
                        <h2 class="font-display font-bold text-lg text-rose-600 dark:text-rose-400">Zona Bahaya: Hapus Akun</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                            Setelah akun Anda dihapus, semua data keuangan termasuk dompet, transaksi, target tabungan, batas anggaran, dan riwayat hutang piutang akan dihapus permanen dari sistem.
                        </p>
                    </div>
                </div>
                <button type="button" @click="showDeleteModal = true; deletePassword = ''" class="px-5 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 text-xs font-bold transition-all active:scale-95 flex items-center justify-center gap-1.5 flex-shrink-0">
                    <span class="material-symbols-outlined text-[16px]">delete_forever</span>
                    <span>Hapus Akun Saya</span>
                </button>
            </div>
        </div>

        <!-- MODAL KONFIRMASI HAPUS AKUN -->
        <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDeleteModal" x-transition.opacity @click="showDeleteModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDeleteModal" x-transition.scale.origin-center class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-float transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center shadow-md shadow-rose-600/30">
                                <span class="material-symbols-outlined text-[20px]">delete_forever</span>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Konfirmasi Hapus Akun</h3>
                                <p class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan</p>
                            </div>
                        </div>
                        <button @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                        Apakah Anda yakin ingin menghapus akun ini secara permanen? Masukkan kata sandi akun Anda untuk mengonfirmasi.
                    </p>

                    <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                        @csrf
                        @method('DELETE')

                        <div>
                            <label for="del_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kata Sandi Anda <span class="text-rose-500">*</span></label>
                            <input type="password" id="del_password" name="password" x-model="deletePassword" required placeholder="Masukkan kata sandi saat ini" class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-rose-500 focus:border-rose-500 font-medium">
                            @if ($errors->userDeletion->has('password'))
                                <p class="text-rose-500 text-xs font-medium mt-1">{{ $errors->userDeletion->first('password') }}</p>
                            @endif
                        </div>

                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                            <button type="button" @click="showDeleteModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 active:scale-95 transition-all">
                                Ya, Hapus Akun Saya
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
