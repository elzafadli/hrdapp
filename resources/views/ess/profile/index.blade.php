<x-app-layout>
    <div class="py-4 sm:py-6 bg-slate-50 min-h-screen" x-data="{
        activeTab: (new URLSearchParams(window.location.search).get('tab') && ['data-karyawan', 'karir', 'pendidikan', 'pengalaman-kerja', 'pelatihan-skill', 'bpjs'].includes(new URLSearchParams(window.location.search).get('tab'))) ? new URLSearchParams(window.location.search).get('tab') : 'data-karyawan',
        isEditing: {{ (isset($errors) && $errors->any()) ? 'true' : 'false' }},
        isSubmitting: false,
        photoPreview: null,
        copyToast: false,
        toastMessage: '',
        handlePhotoChange(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2097152) {
                    alert('Ukuran foto profil maksimal 2 MB!');
                    event.target.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        copyToClipboard(text, label) {
            if (!text || text === '-' || text === 'Belum Terhubung') return;
            navigator.clipboard.writeText(text);
            this.toastMessage = label + ' berhasil disalin!';
            this.copyToast = true;
            setTimeout(() => { this.copyToast = false; }, 2500);
        }
    }">

        {{-- Floating Toast Notification --}}
        <div x-show="copyToast"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="fixed bottom-5 right-5 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gray-900 text-white text-xs font-semibold shadow-xl border border-gray-700">
            <i class="fa-solid fa-circle-check text-emerald-400"></i>
            <span x-text="toastMessage"></span>
        </div>

        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- Top Breadcrumb Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('timeline.index') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 shadow-xs transition">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 tracking-tight">Profil Karyawan (ESS)</h1>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                <i class="fa-solid fa-user-check text-[9px]"></i> ESS Mandiri
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Informasi data kepegawaian, riwayat karir, dan administrasi mandiri</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('ess.password.edit') }}"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-white border border-gray-200 text-gray-700 hover:text-amber-600 hover:border-amber-300 text-xs font-semibold shadow-xs transition">
                        <i class="fa-solid fa-key text-amber-500 text-xs"></i>
                        <span class="hidden sm:inline">Ubah Password</span>
                    </a>
                </div>
            </div>

            {{-- Alert Notifikasi --}}
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base shrink-0"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base shrink-0"></i>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs sm:text-sm flex items-start gap-3 shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-base shrink-0 mt-0.5"></i>
                    <div class="space-y-1">
                        <span class="font-bold">Terjadi kesalahan pada data yang Anda masukkan:</span>
                        <ul class="list-disc pl-4 text-xs space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- HERO PROFILE BANNER (Mobile-Optimized & Desktop Sleek) --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-200 shadow-xs overflow-hidden">
                {{-- Gradient Banner with Profile Info --}}
                <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-indigo-600 text-white p-5 sm:p-7 relative overflow-hidden">
                    {{-- Ambient Shapes --}}
                    <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
                    <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-white/10 pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full border-4 border-white/90 shadow-xl bg-white/20 flex items-center justify-center overflow-hidden mx-auto backdrop-blur-xs">
                                @if($employee->photo_url)
                                    <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-2xl sm:text-3xl font-black text-white tracking-wider">
                                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0 space-y-2">
                            <div>
                                <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">{{ $employee->name }}</h2>
                                <div class="mt-1 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold border border-white/25">
                                        <i class="fa-solid fa-briefcase text-[10px]"></i>
                                        <span>{{ $employee->jabatan?->jabatan ?? 'Staff Karyawan' }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-black/20 text-xs font-mono font-medium border border-white/10">
                                        <i class="fa-solid fa-building text-[10px]"></i>
                                        <span>{{ $employee->effective_company?->name ?? 'TJTM' }}</span>
                                    </span>
                                </div>
                            </div>

                            {{-- Meta Badges & Quick Copy --}}
                            <div class="pt-1 flex flex-wrap items-center justify-center sm:justify-start gap-2 sm:gap-3 text-xs text-blue-100">
                                <button type="button" @click="copyToClipboard('{{ $employee->employee_id_display }}', 'ID Karyawan')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/20 hover:bg-black/30 font-mono text-[11px] border border-white/10 transition cursor-pointer">
                                    <span>ID: {{ $employee->employee_id_display }}</span>
                                    <i class="fa-regular fa-copy text-[10px] text-blue-200"></i>
                                </button>

                                @if($employee->nik_ktp)
                                <button type="button" @click="copyToClipboard('{{ $employee->nik_ktp }}', 'NIK')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/20 hover:bg-black/30 font-mono text-[11px] border border-white/10 transition cursor-pointer">
                                    <span>NIK: {{ $employee->nik_ktp }}</span>
                                    <i class="fa-regular fa-copy text-[10px] text-blue-200"></i>
                                </button>
                                @endif

                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/20 font-mono text-[11px] border border-white/10">
                                    <i class="fa-regular fa-envelope text-[10px]"></i>
                                    <span>{{ $employee->effective_email ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Summary Stats Strip --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-100 bg-slate-50/70 border-t border-gray-100 text-center">
                    <div class="p-3 sm:p-4">
                        <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Status Kerja</span>
                        <span class="block mt-0.5 text-xs sm:text-sm font-bold text-gray-800">
                            {{ $employee->currentCareer()?->status_karyawan ?? 'PKWT' }}
                        </span>
                    </div>
                    <div class="p-3 sm:p-4">
                        <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Tanggal Bergabung</span>
                        <span class="block mt-0.5 text-xs sm:text-sm font-bold text-gray-800 font-mono">
                            {{ $employee->formatted_join_date ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 sm:p-4">
                        <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Masa Kerja</span>
                        <span class="block mt-0.5 text-xs sm:text-sm font-bold text-blue-600">
                            {{ $employee->masa_kerja }}
                        </span>
                    </div>
                    <div class="p-3 sm:p-4">
                        <span class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Penempatan</span>
                        <span class="block mt-0.5 text-xs sm:text-sm font-bold text-gray-800 truncate" title="{{ $employee->project?->name ?? 'Head Office' }}">
                            {{ $employee->project?->name ?? 'Head Office' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- MOBILE TAB SELECTOR (DROPDOWN ON SMALL SCREEN) --}}
            <div class="sm:hidden">
                <label for="mobile-tab-select" class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Pilih Menu Profil
                </label>
                <div class="relative">
                    <select id="mobile-tab-select" x-model="activeTab"
                        class="block w-full rounded-xl border-gray-200 bg-white py-2.5 pl-3 pr-10 text-xs font-semibold text-gray-800 shadow-xs focus:border-blue-500 focus:ring-blue-500">
                        <option value="data-karyawan">👤 Data Karyawan</option>
                        <option value="karir">💼 Riwayat Karir</option>
                        <option value="pendidikan">🎓 Pendidikan</option>
                        <option value="pengalaman-kerja">🏢 Pengalaman Kerja</option>
                        <option value="pelatihan-skill">🏆 Pelatihan & Keahlian</option>
                        <option value="bpjs">🛡️ Data BPJS</option>
                    </select>
                </div>
            </div>

            {{-- HORIZONTAL SCROLLABLE TABS BAR (VISIBLE ON ALL SCREENS, TOUCH-FRIENDLY) --}}
            <div class="bg-white rounded-2xl border border-gray-200/80 p-1.5 shadow-xs overflow-x-auto no-scrollbar">
                <nav class="flex items-center gap-1 min-w-max">
                    <button type="button" @click="activeTab = 'data-karyawan'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'data-karyawan' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-regular fa-user text-xs"></i>
                        <span>Data Karyawan</span>
                    </button>

                    <button type="button" @click="activeTab = 'karir'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'karir' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-solid fa-briefcase text-xs"></i>
                        <span>Karir</span>
                    </button>

                    <button type="button" @click="activeTab = 'pendidikan'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'pendidikan' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-solid fa-graduation-cap text-xs"></i>
                        <span>Pendidikan</span>
                    </button>

                    <button type="button" @click="activeTab = 'pengalaman-kerja'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'pengalaman-kerja' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-solid fa-building-user text-xs"></i>
                        <span>Pengalaman Kerja</span>
                    </button>

                    <button type="button" @click="activeTab = 'pelatihan-skill'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'pelatihan-skill' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-solid fa-award text-xs"></i>
                        <span>Pelatihan & Skill</span>
                    </button>

                    <button type="button" @click="activeTab = 'bpjs'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition"
                        :class="activeTab === 'bpjs' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                        <i class="fa-solid fa-shield-heart text-xs"></i>
                        <span>BPJS</span>
                    </button>
                </nav>
            </div>

            {{-- TAB CONTENT PANELS --}}
            <div class="space-y-6">

                {{-- TAB 1: DATA KARYAWAN (VIEW & EDIT MODE) --}}
                <div x-show="activeTab === 'data-karyawan'" x-cloak class="space-y-4 sm:space-y-6">

                    {{-- Header Aksi Mode Ubah / Lihat --}}
                    <div class="flex items-center justify-between p-4 rounded-2xl bg-white border border-gray-200/80 shadow-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-gray-900" x-text="isEditing ? 'Mode Edit Data Karyawan' : 'Data Karyawan Mandiri'"></h3>
                                <p class="text-[11px] text-gray-500" x-text="isEditing ? 'Perbarui data pribadi dan foto profil resmi Anda di bawah ini.' : 'Sebagai karyawan, Anda dapat mengubah data pribadi dan foto profil secara mandiri.'"></p>
                            </div>
                        </div>

                        <div>
                            <button type="button" x-show="!isEditing" @click="isEditing = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Ubah Data</span>
                            </button>

                            <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                                <i class="fa-solid fa-xmark"></i>
                                <span>Batal</span>
                            </button>
                        </div>
                    </div>

                    {{-- VIEW MODE: KETIKA TIDAK SEDANG EDIT --}}
                    <div x-show="!isEditing" class="space-y-4 sm:space-y-6">

                        {{-- Identitas Akun Login (TERKUNCI / TIDAK DAPAT DIEDIT) --}}
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                            <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                        <i class="fa-solid fa-id-badge"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Identitas & Akun Terdaftar</h3>
                                        <p class="text-[11px] text-gray-500">Nomor induk dan informasi login sistem</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-lock text-[9px]"></i> Terkunci (Dikelola HRD)
                                </span>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Nomor Induk Karyawan (NIK / ID)')" />
                                        <div class="relative mt-1">
                                            <x-text-input type="text"
                                                class="block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed pr-10"
                                                :value="$employee->employee_id_display" disabled />
                                            <button type="button" @click="copyToClipboard('{{ $employee->employee_id_display }}', 'ID Karyawan')"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 cursor-pointer" title="Salin ID">
                                                <i class="fa-regular fa-copy text-xs"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Akun Login ESS Terhubung')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->user ? $employee->user->name . ' (' . $employee->user->email . ')' : 'Belum Terhubung'" disabled />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex items-center justify-between">
                                        <x-input-label :value="__('Email Resmi Karyawan')" />
                                        <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded flex items-center gap-1">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD
                                        </span>
                                    </div>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                            <i class="fa-regular fa-envelope text-sm"></i>
                                        </div>
                                        <x-text-input type="text"
                                            class="block w-full pl-10 bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->effective_email ?? '-'" disabled />
                                    </div>
                                </div>

                                <p class="text-[11px] text-gray-500 mt-3 pt-2.5 border-t border-gray-100 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-info text-blue-500"></i>
                                    <span>Nomor Induk Karyawan, Email, & Akun Login ini ditetapkan secara resmi oleh tim HRD dan tidak dapat diedit secara mandiri.</span>
                                </p>
                            </div>
                        </div>

                        {{-- Data Pribadi Karyawan --}}
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                            <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                        <i class="fa-regular fa-id-card"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Data Pribadi Karyawan</h3>
                                        <p class="text-[11px] text-gray-500">Informasi identitas kependudukan dan status personal</p>
                                    </div>
                                </div>
                                <button type="button" @click="isEditing = true"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-pen text-[10px]"></i> Edit
                                </button>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                    <div class="sm:col-span-2">
                                        <x-input-label :value="__('Nama Lengkap (Sesuai KTP)')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full text-sm font-bold bg-slate-50 text-slate-900 cursor-not-allowed"
                                            :value="$employee->name" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Jenis Kelamin')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->gender_label" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Kewarganegaraan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->citizenship ?? 'WNI (Warga Negara Indonesia)'" disabled />
                                    </div>

                                    <div class="sm:col-span-2">
                                        <x-input-label :value="__('Nomor KTP / NIK')" />
                                        <div class="relative mt-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fa-regular fa-address-card text-xs"></i>
                                            </div>
                                            <x-text-input type="text"
                                                class="block w-full pl-9 pr-10 font-mono tracking-wider bg-slate-50 text-slate-800 cursor-not-allowed"
                                                :value="$employee->nik_ktp ?? '-'" disabled />
                                            @if($employee->nik_ktp)
                                                <button type="button" @click="copyToClipboard('{{ $employee->nik_ktp }}', 'Nomor KTP')"
                                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 cursor-pointer" title="Salin NIK">
                                                    <i class="fa-regular fa-copy text-xs"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Tempat Lahir')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->birth_place ?? '-'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Tanggal Lahir')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->formatted_birth_date ?? '-'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Status Perkawinan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->marital_status ?? '-'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Agama')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->religion ?? '-'" disabled />
                                    </div>

                                    <div class="sm:col-span-2">
                                        <x-input-label :value="__('Golongan Darah')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->blood_type ?? '-'" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 1C: PENUGASAN & STATUS PAYROLL --}}
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                            <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Penugasan & Status Payroll</h3>
                                        <p class="text-[11px] text-gray-500">Penempatan entitas perusahaan, jabatan, project, dan perpajakan</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD
                                </span>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Perusahaan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-bold bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="($employee->effective_company?->name ?? '-') . ' (' . ($employee->effective_company?->code ?? '-') . ')'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Jabatan Struktural')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-bold bg-slate-50 text-blue-600 cursor-not-allowed"
                                            :value="$employee->jabatan?->jabatan ?? '-'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Project Penugasan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->project?->name ?? 'Head Office / Non-Project'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Status PTKP Pajak')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->status_ptkp ?? '-'" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 1D: LAMA BEKERJA --}}
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                            <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                        <i class="fa-regular fa-calendar-check"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Lama Bekerja</h3>
                                        <p class="text-[11px] text-gray-500">Masa pengabdian dan tanggal mulai bergabung karyawan</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                    <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD
                                </span>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Mulai Kerja (Tanggal Bergabung)')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->formatted_join_date ?? '-'" disabled />
                                        <p class="text-[11px] text-gray-400 mt-1">Tanggal resmi karyawan mulai bekerja di perusahaan</p>
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Total Masa Kerja (Kalkulasi Sistem)')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-bold text-blue-700 bg-slate-50 cursor-not-allowed text-base"
                                            :value="$employee->masa_kerja" disabled />
                                        <p class="text-[11px] text-gray-400 mt-1">Dihitung otomatis secara real-time dari tanggal mulai kerja</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pendidikan Formal Terakhir (Di-hide) --}}
                        {{--
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                            <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-bold text-gray-900">Pendidikan Terakhir</h3>
                                        <p class="text-[11px] text-gray-500">Kualifikasi jenjang pendidikan formal terakhir</p>
                                    </div>
                                </div>
                                <button type="button" @click="isEditing = true"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1 cursor-pointer">
                                    <i class="fa-solid fa-pen text-[10px]"></i> Edit
                                </button>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label :value="__('Jenjang Pendidikan Terakhir')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed font-semibold"
                                            :value="$employee->last_education ?? '-'" disabled />
                                    </div>

                                    <div>
                                        <x-input-label :value="__('Nama Institusi / Kampus / Sekolah')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->education_institution ?? '-'" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>
                        --}}

                    </div>

                    {{-- EDIT MODE: FORMULIR UBAH DATA KARYAWAN OLEH USER ESS --}}
                    <div x-show="isEditing" style="display: none;" class="space-y-4 sm:space-y-6">
                        <form method="POST" action="{{ route('ess.profile.update') }}" enctype="multipart/form-data" @submit="isSubmitting = true">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4 sm:space-y-6">

                                {{-- FORM CARD: IDENTITAS & AKUN TERDAFTAR (TERKUNCI / TIDAK DAPAT DIUBAH) --}}
                                <div class="bg-amber-50/40 rounded-2xl border border-amber-200/80 shadow-xs overflow-hidden">
                                    <div class="px-4 sm:px-6 py-3 border-b border-amber-200/60 flex items-center justify-between bg-amber-100/40">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-md bg-amber-200 text-amber-800 flex items-center justify-center text-xs">
                                                <i class="fa-solid fa-lock"></i>
                                            </span>
                                            <div>
                                                <h4 class="text-xs font-bold text-amber-900">Identitas & Akun Terdaftar</h4>
                                                <p class="text-[10px] text-amber-700">Nomor induk dan informasi login sistem (Terkunci & Tidak Dapat Diubah)</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-amber-800 border border-amber-300">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD
                                        </span>
                                    </div>

                                    <div class="p-4 sm:p-5">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label :value="__('Nomor Induk Karyawan (NIK / ID)')" />
                                                <x-text-input type="text"
                                                    class="mt-1 block w-full font-mono bg-slate-100/90 text-slate-700 cursor-not-allowed"
                                                    :value="$employee->employee_id_display" disabled />
                                            </div>

                                            <div>
                                                <x-input-label :value="__('Akun Login ESS Terhubung')" />
                                                <x-text-input type="text"
                                                    class="mt-1 block w-full bg-slate-100/90 text-slate-700 cursor-not-allowed"
                                                    :value="$employee->user ? $employee->user->name . ' (' . $employee->user->email . ')' : 'Belum Terhubung'" disabled />
                                            </div>
                                        </div>
                                        <p class="text-[11px] text-amber-800/80 mt-2 flex items-center gap-1.5">
                                            <i class="fa-solid fa-shield-halved text-amber-600"></i>
                                            <span>Nomor Induk Karyawan & Akun Login ini bersifat permanen dan tidak dapat diubah oleh pengguna.</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- FORM CARD 1: FOTO PROFIL --}}
                                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                                    <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center gap-2.5 bg-blue-50/50">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                            <i class="fa-regular fa-image"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-bold text-gray-900">Ubah Foto Profil</h3>
                                            <p class="text-[11px] text-gray-500">Unggah foto profil formal terbaru Anda (Maksimal 2 MB)</p>
                                        </div>
                                    </div>

                                    <div class="p-4 sm:p-6">
                                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6">
                                            {{-- Avatar Preview --}}
                                            <div class="relative shrink-0">
                                                <div class="w-24 h-24 rounded-full border-4 border-slate-100 shadow-md bg-slate-100 flex items-center justify-center overflow-hidden">
                                                    <template x-if="photoPreview">
                                                        <img :src="photoPreview" alt="Preview Foto" class="w-full h-full object-cover">
                                                    </template>
                                                    <template x-if="!photoPreview">
                                                        @if($employee->photo_url)
                                                            <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                                                        @else
                                                            <span class="text-2xl font-black text-blue-600">
                                                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                                                            </span>
                                                        @endif
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="flex-1 w-full space-y-2">
                                                <x-input-label for="photo" :value="__('Pilih Berkas Foto (JPG, PNG, WEBP)')" />
                                                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp"
                                                    @change="handlePhotoChange($event)"
                                                    class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer border border-gray-200 rounded-xl p-1 bg-slate-50/50" />
                                                <x-input-error class="mt-1" :messages="$errors?->get('photo')" />
                                                <p class="text-[11px] text-gray-400">Gunakan foto close-up wajah formal dengan latar belakang polos.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- FORM CARD 2: DATA PRIBADI --}}
                                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                                    <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/70">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                            <i class="fa-regular fa-id-card"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-bold text-gray-900">Formulir Data Pribadi</h3>
                                            <p class="text-[11px] text-gray-500">Sesuaikan dengan kartu identitas KTP yang masih berlaku</p>
                                        </div>
                                    </div>

                                    <div class="p-4 sm:p-6 space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                            {{-- Nama Lengkap --}}
                                            <div class="sm:col-span-2">
                                                <x-input-label for="name" :value="__('Nama Lengkap (Sesuai KTP) *')" />
                                                <x-text-input id="name" name="name" type="text"
                                                    class="mt-1 block w-full text-sm font-semibold"
                                                    :value="old('name', $employee->name)" required />
                                                <x-input-error class="mt-1" :messages="$errors?->get('name')" />
                                            </div>

                                            {{-- Nomor KTP / NIK --}}
                                            <div class="sm:col-span-2">
                                                <x-input-label for="nik_ktp" :value="__('Nomor KTP / NIK (16 Digit)')" />
                                                <div class="relative mt-1">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                        <i class="fa-regular fa-address-card text-xs"></i>
                                                    </div>
                                                    <x-text-input id="nik_ktp" name="nik_ktp" type="text"
                                                        class="block w-full pl-9 font-mono tracking-wider"
                                                        :value="old('nik_ktp', $employee->nik_ktp)" placeholder="Contoh: 3271xxxxxxxxxxxx" maxlength="20" />
                                                </div>
                                                <x-input-error class="mt-1" :messages="$errors?->get('nik_ktp')" />
                                            </div>

                                            {{-- Jenis Kelamin --}}
                                            <div>
                                                <x-input-label for="gender" :value="__('Jenis Kelamin')" />
                                                <x-select-input :noSelect2="true" id="gender" name="gender" class="mt-1">
                                                    <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Laki-laki</option>
                                                    <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Perempuan</option>
                                                </x-select-input>
                                                <x-input-error class="mt-1" :messages="$errors?->get('gender')" />
                                            </div>

                                            {{-- Kewarganegaraan --}}
                                            <div>
                                                <x-input-label for="citizenship" :value="__('Kewarganegaraan')" />
                                                <x-text-input id="citizenship" name="citizenship" type="text"
                                                    class="mt-1 block w-full"
                                                    :value="old('citizenship', $employee->citizenship ?? 'WNI')" />
                                                <x-input-error class="mt-1" :messages="$errors?->get('citizenship')" />
                                            </div>

                                            {{-- Tempat Lahir --}}
                                            <div>
                                                <x-input-label for="birth_place" :value="__('Tempat Lahir')" />
                                                <x-text-input id="birth_place" name="birth_place" type="text"
                                                    class="mt-1 block w-full"
                                                    :value="old('birth_place', $employee->birth_place)" placeholder="Kota / Kabupaten Lahir" />
                                                <x-input-error class="mt-1" :messages="$errors?->get('birth_place')" />
                                            </div>

                                            {{-- Tanggal Lahir --}}
                                            <div>
                                                <x-input-label for="birth_date" :value="__('Tanggal Lahir')" />
                                                <x-text-input id="birth_date" name="birth_date" type="date"
                                                    class="mt-1 block w-full font-mono"
                                                    :value="old('birth_date', $employee->birth_date ? $employee->birth_date->format('Y-m-d') : '')" />
                                                <x-input-error class="mt-1" :messages="$errors?->get('birth_date')" />
                                            </div>

                                            {{-- Status Perkawinan --}}
                                            <div>
                                                <x-input-label for="marital_status" :value="__('Status Perkawinan')" />
                                                <x-select-input :noSelect2="true" id="marital_status" name="marital_status" class="mt-1">
                                                    @foreach(['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $mStatus)
                                                        <option value="{{ $mStatus }}" {{ old('marital_status', $employee->marital_status) === $mStatus ? 'selected' : '' }}>
                                                            {{ $mStatus }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                                <x-input-error class="mt-1" :messages="$errors?->get('marital_status')" />
                                            </div>

                                            {{-- Agama --}}
                                            <div>
                                                <x-input-label for="religion" :value="__('Agama')" />
                                                <x-select-input :noSelect2="true" id="religion" name="religion" class="mt-1">
                                                    @foreach(['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu', 'Lainnya'] as $rel)
                                                        <option value="{{ $rel }}" {{ old('religion', $employee->religion) === $rel ? 'selected' : '' }}>
                                                            {{ $rel }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                                <x-input-error class="mt-1" :messages="$errors?->get('religion')" />
                                            </div>

                                            {{-- Golongan Darah --}}
                                            <div class="sm:col-span-2">
                                                <x-input-label for="blood_type" :value="__('Golongan Darah')" />
                                                <x-select-input :noSelect2="true" id="blood_type" name="blood_type" class="mt-1">
                                                    @foreach(['-', 'A', 'B', 'AB', 'O'] as $bType)
                                                        <option value="{{ $bType }}" {{ old('blood_type', $employee->blood_type) === $bType ? 'selected' : '' }}>
                                                            {{ $bType }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                                <x-input-error class="mt-1" :messages="$errors?->get('blood_type')" />
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {{-- FORM CARD 3: PENDIDIKAN TERAKHIR (Di-hide) --}}
                                {{--
                                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                                    <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/70">
                                        <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                            <i class="fa-solid fa-graduation-cap"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xs sm:text-sm font-bold text-gray-900">Pendidikan Terakhir</h3>
                                            <p class="text-[11px] text-gray-500">Kualifikasi jenjang pendidikan formal terakhir Anda</p>
                                        </div>
                                    </div>

                                    <div class="p-4 sm:p-6">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label for="last_education" :value="__('Jenjang Pendidikan Terakhir')" />
                                                <x-select-input :noSelect2="true" id="last_education" name="last_education" class="mt-1">
                                                    @foreach(['SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3', 'Lainnya'] as $eduOpt)
                                                        <option value="{{ $eduOpt }}" {{ old('last_education', $employee->last_education) === $eduOpt ? 'selected' : '' }}>
                                                            {{ $eduOpt }}
                                                        </option>
                                                    @endforeach
                                                </x-select-input>
                                                <x-input-error class="mt-1" :messages="$errors?->get('last_education')" />
                                            </div>

                                            <div>
                                                <x-input-label for="education_institution" :value="__('Nama Institusi / Sekolah / Kampus')" />
                                                <x-text-input id="education_institution" name="education_institution" type="text"
                                                    class="mt-1 block w-full"
                                                    :value="old('education_institution', $employee->education_institution)" placeholder="Nama universitas / sekolah" />
                                                <x-input-error class="mt-1" :messages="$errors?->get('education_institution')" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                --}}

                                {{-- FORM CARD 4: DATA TERKUNCI (DIKELOLA OLEH TIM HRD) --}}
                                <div class="bg-amber-50/50 rounded-2xl border border-amber-200/80 p-4 sm:p-6 space-y-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-800 flex items-center justify-center text-xs">
                                            <i class="fa-solid fa-lock"></i>
                                        </span>
                                        <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Data Kepegawaian (Terkunci oleh HRD)</h4>
                                    </div>
                                    <p class="text-xs text-amber-800/80 leading-relaxed">
                                        Informasi penugasan perusahaan, jabatan struktural, project penempatan, dan status pajak dikelola terpusat oleh divisi HRD. Jika ada ketidaksesuaian data di bawah, silakan hubungi tim HRD.
                                    </p>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Perusahaan:</span>
                                            <span class="block text-xs font-bold text-gray-800 mt-0.5">{{ $employee->effective_company?->name ?? 'TJTM' }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Jabatan:</span>
                                            <span class="block text-xs font-bold text-blue-700 mt-0.5">{{ $employee->jabatan?->jabatan ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Project:</span>
                                            <span class="block text-xs font-bold text-gray-800 mt-0.5">{{ $employee->project?->name ?? 'Head Office' }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Status Pajak:</span>
                                            <span class="block text-xs font-mono font-bold text-gray-800 mt-0.5">{{ $employee->status_ptkp ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Mulai Kerja:</span>
                                            <span class="block text-xs font-mono font-bold text-gray-800 mt-0.5">{{ $employee->formatted_join_date ?? '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[11px] text-gray-500 font-medium">Masa Kerja:</span>
                                            <span class="block text-xs font-bold text-blue-700 mt-0.5">{{ $employee->masa_kerja }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- TOMBOL AKSI SUBMIT (ANTI DOUBLE-SUBMIT) --}}
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <x-secondary-button type="button" @click="isEditing = false">
                                        Batal
                                    </x-secondary-button>

                                    <x-primary-button type="submit" ::disabled="isSubmitting" class="shadow-sm">
                                        <span x-show="!isSubmitting" class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-check"></i>
                                            Simpan Perubahan
                                        </span>
                                        <span x-show="isSubmitting" style="display: none;" class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                                            Menyimpan...
                                        </span>
                                    </x-primary-button>
                                </div>

                            </div>
                        </form>
                    </div>

                </div>

                {{-- TAB 2: KARIR --}}
                <div x-show="activeTab === 'karir'" x-cloak class="space-y-4 sm:space-y-6">
                    @php
                        $currentCareer = $employee->currentCareer();
                        $careerList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_careers')) ? $employee->careers : collect();
                    @endphp

                    {{-- Card Posisi Terkini --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-blue-50/50">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                    <i class="fa-solid fa-briefcase"></i>
                                </span>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-gray-900">Posisi & Jabatan Saat Ini</h3>
                                    <p class="text-[11px] text-gray-500">Struktur jabatan aktif di perusahaan</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Aktif
                            </span>
                        </div>

                        <div class="p-4 sm:p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label :value="__('Jabatan Struktural')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full font-bold bg-blue-50/40 text-blue-700 cursor-not-allowed border-blue-200"
                                        :value="$currentCareer->jabatan ?? ($employee->jabatan?->jabatan ?? '-')" disabled />
                                </div>

                                <div>
                                    <x-input-label :value="__('Status Kepegawaian')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full font-semibold bg-slate-50 text-slate-800 cursor-not-allowed"
                                        :value="$currentCareer->status_karyawan ?? 'PKWT'" disabled />
                                </div>

                                <div>
                                    <x-input-label :value="__('Penempatan Kerja')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                        :value="$currentCareer->penempatan_kerja ?? ($employee->project?->name ?? 'Head Office')" disabled />
                                </div>

                                <div>
                                    <x-input-label :value="__('Tanggal Efektif')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                        :value="$currentCareer ? $currentCareer->formatted_tanggal_efektif : ($employee->formatted_join_date ?? '-')" disabled />
                                </div>

                                <div>
                                    <x-input-label :value="__('Organisasi / Divisi')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                        :value="$currentCareer->organisasi ?? 'Operasional'" disabled />
                                </div>

                                <div>
                                    <x-input-label :value="__('Pangkat')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                        :value="$currentCareer->pangkat ?? 'Staff'" disabled />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Riwayat Karir Lengkap --}}
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-gray-400 text-xs sm:text-sm"></i>
                                <h3 class="text-xs sm:text-sm font-bold text-gray-900">Riwayat Mutasi & Promosi ({{ $careerList->count() }})</h3>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            @if($careerList->count() > 0)
                                <div class="space-y-4">
                                    @foreach($careerList as $index => $career)
                                        <div class="p-4 sm:p-5 rounded-xl sm:rounded-2xl border border-gray-200 bg-slate-50/40 space-y-3">
                                            <div class="flex items-center justify-between pb-2 border-b border-gray-200/80">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center">
                                                        {{ $loop->iteration }}
                                                    </span>
                                                    <span class="text-xs font-bold text-gray-900">{{ $career->jabatan ?? '-' }}</span>
                                                    @if($index === 0)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Terkini</span>
                                                    @endif
                                                </div>
                                                <span class="text-[11px] font-mono text-gray-500">Efektif: {{ $career->formatted_tanggal_efektif }}</span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label :value="__('Status')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-white text-slate-800 text-xs cursor-not-allowed" :value="$career->status_karyawan ?? '-'" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Penempatan')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-white text-slate-800 text-xs cursor-not-allowed" :value="$career->penempatan_kerja ?? '-'" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Divisi')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-white text-slate-800 text-xs cursor-not-allowed" :value="$career->organisasi ?? '-'" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Pangkat')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-white text-slate-800 text-xs cursor-not-allowed" :value="$career->pangkat ?? '-'" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-xs text-gray-400">
                                    <i class="fa-solid fa-briefcase text-2xl text-gray-300 mb-2 block"></i>
                                    Belum ada catatan riwayat mutasi / karir tambahan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 3: PENDIDIKAN (BISA MULTI) --}}
                <div x-show="activeTab === 'pendidikan'" x-cloak class="space-y-4 sm:space-y-6">
                    @php
                        $educationList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_educations')) ? $employee->educations : collect();
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                    <i class="fa-solid fa-graduation-cap"></i>
                                </span>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-gray-900">Riwayat Pendidikan Formal ({{ $educationList->count() }})</h3>
                                    <p class="text-[11px] text-gray-500">Jenjang pendidikan, almamater, dan kelulusan</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            @if($educationList->count() > 0)
                                <div class="space-y-4">
                                    @foreach($educationList as $edu)
                                        <div class="p-4 sm:p-5 rounded-xl sm:rounded-2xl border border-gray-200 bg-white space-y-3 shadow-2xs">
                                            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center">
                                                        <i class="fa-solid fa-graduation-cap text-[10px]"></i>
                                                    </div>
                                                    <span class="text-xs sm:text-sm font-bold text-gray-900">{{ $edu->institusi }}</span>
                                                </div>
                                                <span class="text-[11px] font-mono text-gray-500 font-semibold">{{ $edu->period }}</span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label :value="__('Jenjang')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed font-semibold" :value="$edu->jenjang" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Jurusan / Program Studi')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$edu->jurusan ?? '-'" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Nilai Akhir / IPK')" />
                                                    <x-text-input type="text" class="mt-1 block w-full font-mono bg-slate-50 text-amber-700 font-bold text-xs cursor-not-allowed" :value="$edu->nilai ?? '-'" disabled />
                                                </div>
                                                @if($edu->notes)
                                                <div>
                                                    <x-input-label :value="__('Catatan')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-700 text-xs cursor-not-allowed italic" :value="$edu->notes" disabled />
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-xs text-gray-400">
                                    <i class="fa-solid fa-graduation-cap text-2xl text-gray-300 mb-2 block"></i>
                                    Belum ada data riwayat pendidikan tersimpan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 4: PENGALAMAN KERJA (BISA MULTI) --}}
                <div x-show="activeTab === 'pengalaman-kerja'" x-cloak class="space-y-4 sm:space-y-6">
                    @php
                        $experienceList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_experiences')) ? $employee->experiences : collect();
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                    <i class="fa-solid fa-building-user"></i>
                                </span>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-gray-900">Riwayat Pengalaman Kerja ({{ $experienceList->count() }})</h3>
                                    <p class="text-[11px] text-gray-500">Perjalanan karir dan pengalaman kerja profesional sebelumnya</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            @if($experienceList->count() > 0)
                                <div class="space-y-4">
                                    @foreach($experienceList as $exp)
                                        <div class="p-4 sm:p-5 rounded-xl sm:rounded-2xl border border-gray-200 bg-white space-y-3 shadow-2xs">
                                            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                                                        <i class="fa-solid fa-briefcase text-[10px]"></i>
                                                    </span>
                                                    <div>
                                                        <span class="text-xs sm:text-sm font-bold text-gray-900">{{ $exp->jabatan }}</span>
                                                        <span class="text-xs text-gray-500 font-normal"> di {{ $exp->perusahaan }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-[11px] font-mono text-gray-500 font-semibold">{{ $exp->period }}</span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label :value="__('Perusahaan')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$exp->perusahaan" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Jabatan / Peran')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed font-semibold" :value="$exp->jabatan" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Periode Bekerja')" />
                                                    <x-text-input type="text" class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$exp->period" disabled />
                                                </div>
                                                @if($exp->deskripsi_pekerjaan)
                                                <div class="sm:col-span-2">
                                                    <x-input-label :value="__('Deskripsi / Tugas Utama')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$exp->deskripsi_pekerjaan" disabled />
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-xs text-gray-400">
                                    <i class="fa-solid fa-building-user text-2xl text-gray-300 mb-2 block"></i>
                                    Belum ada data pengalaman kerja masa lampau yang dicatat.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 5: TRAINING & SKILL (BISA MULTI) --}}
                <div x-show="activeTab === 'pelatihan-skill'" x-cloak class="space-y-4 sm:space-y-6">
                    @php
                        $trainingList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_trainings')) ? $employee->trainings : collect();
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-slate-50/70">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                    <i class="fa-solid fa-award"></i>
                                </span>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-gray-900">Pelatihan, Kursus & Keahlian ({{ $trainingList->count() }})</h3>
                                    <p class="text-[11px] text-gray-500">Sertifikasi profesi dan workshop kompetensi</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            @if($trainingList->count() > 0)
                                <div class="space-y-4">
                                    @foreach($trainingList as $tr)
                                        <div class="p-4 sm:p-5 rounded-xl sm:rounded-2xl border border-gray-200 bg-white space-y-3 shadow-2xs">
                                            <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-700 font-bold text-xs flex items-center justify-center">
                                                        <i class="fa-solid fa-award text-[10px]"></i>
                                                    </span>
                                                    <span class="text-xs sm:text-sm font-bold text-gray-900">{{ $tr->nama_pelatihan }}</span>
                                                </div>
                                                <span class="text-[11px] font-mono text-gray-500 font-semibold">{{ $tr->tahun ?? '-' }}</span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <x-input-label :value="__('Lembaga Penyelenggara')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$tr->penyelenggara" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Nomor Sertifikat')" />
                                                    <x-text-input type="text" class="mt-1 block w-full font-mono bg-slate-50 text-blue-700 font-bold text-xs cursor-not-allowed" :value="$tr->nomor_sertifikat ?? '-'" disabled />
                                                </div>
                                                <div>
                                                    <x-input-label :value="__('Masa Berlaku')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-800 text-xs cursor-not-allowed" :value="$tr->masa_berlaku ?? 'Seumur Hidup'" disabled />
                                                </div>
                                                @if($tr->deskripsi)
                                                <div>
                                                    <x-input-label :value="__('Deskripsi / Materi')" />
                                                    <x-text-input type="text" class="mt-1 block w-full bg-slate-50 text-slate-700 text-xs cursor-not-allowed" :value="$tr->deskripsi" disabled />
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-xs text-gray-400">
                                    <i class="fa-solid fa-award text-2xl text-gray-300 mb-2 block"></i>
                                    Belum ada data pelatihan atau sertifikasi kompetensi yang dicatat.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- TAB 6: DATA BPJS --}}
                <div x-show="activeTab === 'bpjs'" x-cloak class="space-y-4 sm:space-y-6">
                    @php
                        $bpjsMap = \Illuminate\Support\Facades\Schema::hasTable('emp_bpjs')
                            ? $employee->bpjs->pluck('value', 'payroll_component_id')->toArray()
                            : [];
                    @endphp
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                        <div class="px-4 sm:px-6 py-3.5 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/70">
                            <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs sm:text-sm shrink-0">
                                <i class="fa-solid fa-shield-heart"></i>
                            </span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-gray-900">Jaminan Sosial & Proteksi Tenaga Kerja (BPJS)</h3>
                                <p class="text-[11px] text-gray-500">Nomor kepesertaan BPJS Kesehatan dan Ketenagakerjaan</p>
                            </div>
                        </div>

                        <div class="p-4 sm:p-6">
                            @if(empty($bpjsComponents) || $bpjsComponents->isEmpty())
                                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                                    <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                                    <span>Komponen BPJS belum dikonfigurasi pada sistem payroll.</span>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    @foreach($bpjsComponents as $comp)
                                        @php
                                            $val = $bpjsMap[$comp->id] ?? null;
                                            $hasVal = !empty($val);
                                        @endphp
                                        <div class="p-4 rounded-xl border {{ $hasVal ? 'border-blue-100 bg-blue-50/20' : 'border-gray-100 bg-slate-50/60' }} transition">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg {{ $hasVal ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center text-xs font-bold shrink-0">
                                                        <i class="fa-solid {{ str_contains(strtolower($comp->name), 'kesehatan') ? 'fa-heart-pulse' : 'fa-shield-halved' }}"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-xs font-bold text-gray-900">{{ $comp->name }}</h4>
                                                        <span class="inline-block text-[10px] font-mono uppercase text-gray-500">
                                                            {{ ucfirst($comp->type) }}
                                                        </span>
                                                    </div>
                                                </div>

                                                @if($hasVal)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        <i class="fa-solid fa-check text-[9px]"></i> Aktif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                                        Belum Diisi
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-3 pt-3 border-t {{ $hasVal ? 'border-blue-100' : 'border-gray-100' }}">
                                                <span class="text-[11px] text-gray-500 block">Nomor Kartu:</span>
                                                <div class="mt-0.5 flex items-center justify-between">
                                                    <span class="text-xs sm:text-sm font-mono font-bold {{ $hasVal ? 'text-gray-900' : 'text-gray-400' }}">
                                                        {{ $hasVal ? $val : '-' }}
                                                    </span>
                                                    @if($hasVal)
                                                        <button type="button" @click="copyToClipboard('{{ $val }}', 'Nomor {{ $comp->name }}')"
                                                            class="text-xs text-blue-600 hover:text-blue-700" title="Salin Nomor">
                                                            <i class="fa-regular fa-copy"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
