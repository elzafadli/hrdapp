<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Top Breadcrumb / Nav --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.index') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 shadow-xs transition">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Profil & Data Karyawan</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Informasi profil lengkap, identitas, dan administrasi kepegawaian</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('employee.edit', $employee) }}"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Edit Data Karyawan
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="space-y-6" x-data="{
                activeTab: (function() {
                    try {
                        const p = new URLSearchParams(window.location.search).get('tab');
                        if (p && ['data-karyawan', 'karir', 'pendidikan', 'pengalaman-kerja', 'pelatihan-skill', 'bpjs'].includes(p)) {
                            return p;
                        }
                    } catch(e) {}
                    return '{{ request('tab', 'data-karyawan') }}';
                })(),
                copyToast: false,
                toastMessage: '',
                copyKtp(text) {
                    if (!text || text === '-') return;
                    navigator.clipboard.writeText(text);
                    this.toastMessage = 'Nomor KTP berhasil disalin!';
                    this.copyToast = true;
                    setTimeout(() => { this.copyToast = false; }, 2500);
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

                {{-- HERO PROFILE BANNER (Mobile-Optimized & Desktop Sleek) --}}
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-gray-200 shadow-xs overflow-hidden">
                    <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-indigo-600 text-white p-5 sm:p-7 relative overflow-hidden">
                        <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
                        <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full bg-white/10 pointer-events-none"></div>

                        <div class="relative z-10 flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
                            {{-- Avatar Circle --}}
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

                                    @if($employee->effective_phone ?? $employee->phone ?? $employee->user?->phone)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/20 font-mono text-[11px] border border-white/10">
                                        <i class="fa-solid fa-phone text-[10px]"></i>
                                        <span>{{ $employee->effective_phone ?? $employee->phone ?? $employee->user?->phone }}</span>
                                    </div>
                                    @endif
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

                {{-- HORIZONTAL SCROLLABLE TABS BAR (TOUCH-FRIENDLY & CLEAN) --}}
                <div class="bg-white rounded-2xl border border-gray-200/80 p-1.5 shadow-xs overflow-x-auto no-scrollbar">
                    <nav class="flex items-center gap-1 min-w-max">
                        <button type="button" @click="activeTab = 'data-karyawan'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'data-karyawan' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-regular fa-user text-xs"></i>
                            <span>Data Karyawan</span>
                        </button>

                        <button type="button" @click="activeTab = 'karir'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'karir' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-solid fa-briefcase text-xs"></i>
                            <span>Karir</span>
                        </button>

                        <button type="button" @click="activeTab = 'pendidikan'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'pendidikan' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-solid fa-graduation-cap text-xs"></i>
                            <span>Pendidikan</span>
                        </button>

                        <button type="button" @click="activeTab = 'pengalaman-kerja'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'pengalaman-kerja' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-solid fa-building-user text-xs"></i>
                            <span>Pengalaman Kerja</span>
                        </button>

                        <button type="button" @click="activeTab = 'pelatihan-skill'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'pelatihan-skill' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-solid fa-award text-xs"></i>
                            <span>Pelatihan & Skill</span>
                        </button>

                        <button type="button" @click="activeTab = 'bpjs'"
                            class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                            :class="activeTab === 'bpjs' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100/70'">
                            <i class="fa-solid fa-shield-heart text-xs"></i>
                            <span>BPJS</span>
                        </button>
                    </nav>
                </div>

                {{-- TAB CONTENT PANELS --}}
                <div class="space-y-6">

                    {{-- TAB 1: DATA KARYAWAN (TAMPILAN PERSIS FORM DENGAN INPUT DISABLED) --}}
                    <div x-show="activeTab === 'data-karyawan'" class="space-y-6">

                        {{-- CARD 1A: FOTO PROFIL & IDENTITAS UTAMA --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-regular fa-image"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-sm font-bold text-gray-900">Foto Profil & Akun Karyawan</h2>
                                        <p class="text-xs text-gray-500">Foto profil dan akun login user terkait</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'data-karyawan']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Ubah Data
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                                    {{-- Avatar Preview --}}
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="relative w-28 h-28 rounded-full border-4 border-white shadow-md bg-slate-100 flex items-center justify-center overflow-hidden">
                                            @if($employee->photo_url)
                                                <img src="{{ $employee->photo_url }}" alt="{{ $employee->name }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex flex-col items-center justify-center bg-blue-50 text-blue-600">
                                                    <i class="fa-regular fa-user text-3xl"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-gray-500 font-medium text-center max-w-[160px]">
                                            Foto Profil Resmi
                                        </span>
                                    </div>

                                    {{-- Info Input Disabled --}}
                                    <div class="flex-1 w-full space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label :value="__('ID Karyawan (Nomor Induk Karyawan)')" />
                                                <x-text-input type="text"
                                                    class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                    :value="$employee->employee_id_display" disabled />
                                            </div>

                                            <div>
                                                <x-input-label :value="__('Akun Login User (Untuk ESS & Approval)')" />
                                                <x-text-input type="text"
                                                    class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                    :value="$employee->user ? $employee->user->name . ' (' . $employee->user->email . ')' : 'Belum Terhubung'" disabled />
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
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

                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <x-input-label :value="__('No. Handphone / WhatsApp')" />
                                                    <span class="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded flex items-center gap-1">
                                                        <i class="fa-solid fa-arrows-rotate text-[8px]"></i> Sinkron User
                                                    </span>
                                                </div>
                                                <div class="relative mt-1">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                                        <i class="fa-solid fa-phone text-sm"></i>
                                                    </div>
                                                    <x-text-input type="text"
                                                        class="block w-full pl-10 font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                        :value="$employee->effective_phone ?? $employee->phone ?? $employee->user?->phone ?? '-'" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 1B: DATA PRIBADI KARYAWAN (PERSIS FORM) --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/60">
                                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                    <i class="fa-regular fa-id-card"></i>
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-gray-900">Data Pribadi Karyawan</h2>
                                    <p class="text-xs text-gray-500">Informasi identitas, kewarganegaraan, dan kependudukan</p>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">

                                    {{-- Nama Lengkap --}}
                                    <div class="sm:col-span-12">
                                        <x-input-label :value="__('Nama Lengkap (Sesuai KTP)')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full text-base font-semibold bg-slate-50 text-slate-900 cursor-not-allowed"
                                            :value="$employee->name" disabled />
                                    </div>

                                    {{-- Jenis Kelamin --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Jenis Kelamin')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->gender_label" disabled />
                                    </div>

                                    {{-- Kewarganegaraan --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Kewarganegaraan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->citizenship ?? 'WNI (Warga Negara Indonesia)'" disabled />
                                    </div>

                                    {{-- Identitas / No. KTP --}}
                                    <div class="sm:col-span-12">
                                        <div class="flex items-center justify-between">
                                            <x-input-label :value="__('Identitas (Nomor KTP / NIK)')" />
                                            @if($employee->nik_ktp)
                                                <button type="button" @click="copyKtp('{{ $employee->nik_ktp }}')"
                                                    class="text-xs text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1 cursor-pointer">
                                                    <i class="fa-regular fa-copy"></i>
                                                    Salin NIK
                                                </button>
                                            @endif
                                        </div>
                                        <div class="relative mt-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fa-regular fa-address-card text-sm"></i>
                                            </div>
                                            <x-text-input type="text"
                                                class="block w-full pl-9 font-mono tracking-wider bg-slate-50 text-slate-800 cursor-not-allowed"
                                                :value="$employee->nik_ktp ?? '-'" disabled />
                                        </div>
                                    </div>

                                    {{-- Tempat Lahir --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Tempat Lahir')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->birth_place ?? '-'" disabled />
                                    </div>

                                    {{-- Tanggal Lahir --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Tanggal Lahir')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->formatted_birth_date ?? '-'" disabled />
                                    </div>

                                    {{-- Status Perkawinan --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Status Perkawinan')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->marital_status ?? '-'" disabled />
                                    </div>

                                    {{-- Agama --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Agama')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->religion ?? '-'" disabled />
                                    </div>

                                    {{-- Golongan Darah --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label :value="__('Golongan Darah')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->blood_type ?? '-'" disabled />
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- CARD 1C: PENUGASAN & STATUS PAYROLL --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-sm font-bold text-gray-900">Penugasan & Status Payroll</h2>
                                        <p class="text-xs text-gray-500">Keterkaitan penempatan kerja, jabatan, project, dan perpajakan</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'data-karyawan']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Ubah Data
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
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
                                            :value="$employee->project?->name ?? 'Non-Project / Head Office'" disabled />
                                    </div>
                                    <div>
                                        <x-input-label :value="__('Status PTKP Pajak')" />
                                        <x-text-input type="text"
                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                            :value="$employee->status_ptkp ?? '-'" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 1D: LAMA BEKERJA --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-regular fa-calendar-check"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-sm font-bold text-gray-900">Lama Bekerja</h2>
                                        <p class="text-xs text-gray-500">Masa pengabdian dan tanggal mulai bergabung karyawan</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'data-karyawan']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Ubah Data
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
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

                    </div>

                    {{-- TAB 2: KARIR (SESUAI GAMBAR 3) --}}
                    <div x-show="activeTab === 'karir'" class="space-y-6">
                        @php
                            $currentCareer = $employee->currentCareer();
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Karir</h2>
                                        <p class="text-xs text-gray-500">Status penempatan kerja, kepangkatan, dan struktur organisasi</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'karir']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Kelola Karir
                                </a>
                            </div>

                            <div class="p-6 space-y-6">
                                {{-- Card Posisi Terkini Berupa Form Inputs Disabled --}}
                                <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-200 space-y-4">
                                    <div class="flex items-center gap-2 pb-3 border-b border-slate-200">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                                        <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Posisi Saat Ini / Aktif</h3>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Jabatan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-bold bg-white text-blue-700 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer->jabatan ?? ($employee->jabatan?->jabatan ?? '-')" disabled />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Status Karyawan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-white text-slate-800 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer->status_karyawan ?? 'PKWT'" disabled />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Penempatan Kerja')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-white text-slate-800 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer->penempatan_kerja ?? 'Baru Direkrut'" disabled />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Tanggal Efektif')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-white text-slate-800 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer ? $currentCareer->formatted_tanggal_efektif : ($employee->formatted_join_date ?? '-')" disabled />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Organisasi / Divisi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-white text-slate-800 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer->organisasi ?? 'IT'" disabled />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Pangkat')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-white text-slate-800 cursor-not-allowed border-gray-200"
                                                :value="$currentCareer->pangkat ?? 'Staff'" disabled />
                                        </div>
                                    </div>
                                </div>

                                {{-- RIWAYAT KARIR (MULTI) BERUPA FORM TILES DISABLED --}}
                                <div class="pt-2">
                                    @php
                                        $careerList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_careers')) ? $employee->careers : collect();
                                    @endphp
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            SEMUA RIWAYAT KARIR ({{ $careerList->count() }} Tercatat)
                                        </span>
                                    </div>

                                    @if($careerList->count() > 0)
                                        <div class="space-y-4">
                                            @foreach($careerList as $index => $career)
                                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                                <i class="fa-solid fa-briefcase"></i>
                                                            </div>
                                                            <span class="text-xs font-bold text-slate-800">Riwayat Karir #{{ $loop->iteration }}</span>
                                                            @if($index === 0)
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                                                    Posisi Saat Ini
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <span class="text-xs font-mono text-slate-500 font-semibold">Efektif: {{ $career->formatted_tanggal_efektif }}</span>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                        <div>
                                                            <x-input-label :value="__('Jabatan')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                                :value="$career->jabatan ?? '-'" disabled />
                                                        </div>
                                                        <div>
                                                            <x-input-label :value="__('Status Karyawan')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                                :value="$career->status_karyawan ?? '-'" disabled />
                                                        </div>
                                                        <div>
                                                            <x-input-label :value="__('Penempatan Kerja')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                                :value="$career->penempatan_kerja ?? '-'" disabled />
                                                        </div>
                                                        <div>
                                                            <x-input-label :value="__('Tanggal Efektif')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                                :value="$career->formatted_tanggal_efektif ?? '-'" disabled />
                                                        </div>
                                                        <div>
                                                            <x-input-label :value="__('Organisasi / Divisi')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                                :value="$career->organisasi ?? '-'" disabled />
                                                        </div>
                                                        <div>
                                                            <x-input-label :value="__('Pangkat')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                                :value="$career->pangkat ?? '-'" disabled />
                                                        </div>
                                                    </div>

                                                    @if($career->notes)
                                                        <div>
                                                            <x-input-label :value="__('Catatan')" />
                                                            <x-text-input type="text"
                                                                class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                                                :value="$career->notes" disabled />
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 text-center text-xs text-gray-500">
                                            Belum ada data riwayat karir tercatat.
                                            <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'karir']) }}" class="text-blue-600 font-bold hover:underline ml-1">Tambah Karir Sekarang</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- TAB 3: PENDIDIKAN (BISA MULTI) --}}
                    <div x-show="activeTab === 'pendidikan'" class="space-y-6">
                        @php
                            $educationList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_educations')) ? $employee->educations : collect();
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-graduation-cap"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Pendidikan</h2>
                                        <p class="text-xs text-gray-500">Riwayat jenjang pendidikan dan nama instansi akademik</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pendidikan']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Kelola Pendidikan
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        RIWAYAT PENDIDIKAN ({{ $educationList->count() }} Tercatat)
                                    </span>
                                </div>

                                @if($educationList->count() > 0)
                                    <div class="space-y-4">
                                        @foreach($educationList as $edu)
                                            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                            <i class="fa-solid fa-graduation-cap"></i>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-800">{{ $edu->institusi }}</span>
                                                    </div>
                                                    <span class="text-xs font-mono text-slate-500 font-semibold">{{ $edu->period }}</span>
                                                </div>

                                                {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <x-input-label :value="__('Institusi / Sekolah')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                            :value="$edu->institusi" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Jenjang Pendidikan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$edu->jenjang" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Jurusan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$edu->jurusan ?? '-'" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Nilai / IPK')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full font-mono bg-slate-50 text-amber-700 font-bold cursor-not-allowed"
                                                            :value="$edu->nilai ?? '-'" disabled />
                                                    </div>
                                                </div>

                                                @if($edu->notes)
                                                    <div>
                                                        <x-input-label :value="__('Catatan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                                            :value="$edu->notes" disabled />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 text-center text-xs text-gray-500">
                                        Belum ada riwayat pendidikan yang dicatat.
                                        <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pendidikan']) }}" class="text-blue-600 font-bold hover:underline ml-1">Tambah Pendidikan Sekarang</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB 5: PENGALAMAN KERJA (BISA MULTI) --}}
                    <div x-show="activeTab === 'pengalaman-kerja'" class="space-y-6">
                        @php
                            $experienceList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_experiences')) ? $employee->experiences : collect();
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-building-user"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Pengalaman Kerja</h2>
                                        <p class="text-xs text-gray-500">Riwayat pekerjaan dan pengalaman profesional masa lampau</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pengalaman-kerja']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Kelola Pengalaman
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        RIWAYAT PENGALAMAN KERJA ({{ $experienceList->count() }} Tercatat)
                                    </span>
                                </div>

                                @if($experienceList->count() > 0)
                                    <div class="space-y-4">
                                        @foreach($experienceList as $exp)
                                            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                            <i class="fa-solid fa-briefcase"></i>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-800">{{ $exp->jabatan }} di {{ $exp->perusahaan }}</span>
                                                        @if($exp->is_current)
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                                Aktif / Terakhir
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <span class="text-xs font-mono text-slate-500 font-semibold">{{ $exp->period }}</span>
                                                </div>

                                                {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <x-input-label :value="__('Jabatan / Posisi')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                            :value="$exp->jabatan" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Nama Perusahaan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$exp->perusahaan" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Periode Kerja')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$exp->period" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Gaji Terakhir')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$exp->gaji_terakhir ?? '-'" disabled />
                                                    </div>
                                                </div>

                                                @if($exp->deskripsi_pekerjaan)
                                                    <div>
                                                        <x-input-label :value="__('Tanggung Jawab & Deskripsi Pekerjaan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$exp->deskripsi_pekerjaan" disabled />
                                                    </div>
                                                @endif

                                                @if($exp->alasan_berhenti)
                                                    <div>
                                                        <x-input-label :value="__('Alasan Berhenti')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$exp->alasan_berhenti" disabled />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 text-center text-xs text-gray-500">
                                        Belum ada riwayat pengalaman kerja yang dicatat.
                                        <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pengalaman-kerja']) }}" class="text-blue-600 font-bold hover:underline ml-1">Tambah Pengalaman Sekarang</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB 6: TRAINING & SKILL (BISA MULTI) --}}
                    <div x-show="activeTab === 'pelatihan-skill'" class="space-y-6">
                        @php
                            $trainingList = ($employee->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_trainings')) ? $employee->trainings : collect();
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-award"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Pelatihan & Keahlian</h2>
                                        <p class="text-xs text-gray-500">Kursus kompetensi, sertifikasi profesi, dan keahlian kerja</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pelatihan-skill']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Kelola Pelatihan
                                </a>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        DAFTAR PELATIHAN & KEAHLIAN ({{ $trainingList->count() }} Tercatat)
                                    </span>
                                </div>

                                @if($trainingList->count() > 0)
                                    <div class="space-y-4">
                                        @foreach($trainingList as $tr)
                                            <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                            <i class="fa-solid fa-award"></i>
                                                        </div>
                                                        <span class="text-xs font-bold text-slate-800">{{ $tr->nama_pelatihan }}</span>
                                                    </div>
                                                    <span class="text-xs font-mono text-slate-500 font-semibold">{{ $tr->tahun ?? '-' }}</span>
                                                </div>

                                                {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <div>
                                                        <x-input-label :value="__('Nama Pelatihan / Keahlian')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                            :value="$tr->nama_pelatihan" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Lembaga Penyelenggara')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$tr->penyelenggara" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Tahun Pelaksanaan')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$tr->tahun ?? '-'" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Nomor Sertifikat / Lisensi')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full font-mono bg-slate-50 text-blue-700 font-bold cursor-not-allowed"
                                                            :value="$tr->nomor_sertifikat ?? '-'" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Masa Berlaku')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                            :value="$tr->masa_berlaku ?? 'Seumur Hidup'" disabled />
                                                    </div>
                                                    <div>
                                                        <x-input-label :value="__('Deskripsi / Materi')" />
                                                        <x-text-input type="text"
                                                            class="mt-1 block w-full bg-slate-50 text-slate-700 cursor-not-allowed"
                                                            :value="$tr->deskripsi ?? '-'" disabled />
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-6 rounded-2xl bg-slate-50 border border-slate-100 text-center text-xs text-gray-500">
                                        Belum ada data pelatihan atau keahlian yang dicatat.
                                        <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'pelatihan-skill']) }}" class="text-blue-600 font-bold hover:underline ml-1">Tambah Pelatihan Sekarang</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB 6: DATA BPJS --}}
                    @php
                        $bpjsMap = \Illuminate\Support\Facades\Schema::hasTable('emp_bpjs')
                            ? $employee->bpjs->pluck('value', 'payroll_component_id')->toArray()
                            : [];
                    @endphp
                    <div x-show="activeTab === 'bpjs'" class="space-y-6">
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-shield-heart"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-base font-bold text-gray-900">Data BPJS (Kesehatan & Ketenagakerjaan)</h2>
                                        <p class="text-xs text-gray-500">Informasi kepesertaan jaminan sosial dan proteksi tenaga kerja</p>
                                    </div>
                                </div>
                                <a href="{{ route('employee.edit', ['employee' => $employee, 'tab' => 'bpjs']) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                    Kelola BPJS
                                </a>
                            </div>

                            <div class="p-6">
                                @if(empty($bpjsComponents) || $bpjsComponents->isEmpty())
                                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                                        <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                                        <span>Komponen BPJS belum dikonfigurasi pada sistem payroll.</span>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($bpjsComponents as $comp)
                                            @php
                                                $val = $bpjsMap[$comp->id] ?? null;
                                                $hasVal = !empty($val);
                                            @endphp
                                            <div class="p-4 rounded-xl border {{ $hasVal ? 'border-blue-100 bg-blue-50/20' : 'border-gray-100 bg-slate-50/50' }} transition">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-xl {{ $hasVal ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center text-sm font-bold shrink-0">
                                                            <i class="fa-solid {{ str_contains(strtolower($comp->name), 'kesehatan') ? 'fa-heart-pulse' : 'fa-briefcase' }}"></i>
                                                        </div>
                                                        <div>
                                                            <h4 class="text-xs font-bold text-gray-900">{{ $comp->name }}</h4>
                                                            <span class="inline-block mt-0.5 text-[10px] font-mono uppercase text-gray-500">
                                                                {{ ucfirst($comp->type) }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    @if($hasVal)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                            <i class="fa-solid fa-check text-[9px]"></i>
                                                            Aktif
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                                            Belum Diisi
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-3 pt-3 border-t {{ $hasVal ? 'border-blue-100' : 'border-gray-100' }}">
                                                    <span class="text-[11px] text-gray-500 block">Nomor Kartu / Nilai:</span>
                                                    <div class="mt-0.5 text-sm font-mono font-bold {{ $hasVal ? 'text-gray-900' : 'text-gray-400' }}">
                                                        {{ $hasVal ? $val : '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- PLACEHOLDER TAB FOR OTHER SECTIONS --}}
                    <div x-show="!['data-karyawan', 'karir', 'lama-bekerja', 'pendidikan', 'pengalaman-kerja', 'pelatihan-skill', 'penugasan-payroll', 'bpjs'].includes(activeTab)" class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-xs">
                        <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl mx-auto mb-3">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900" x-text="'Modul ' + activeTab"></h3>
                        <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                            Modul ini disiapkan untuk integrasi tahap selanjutnya. Saat ini data utama karyawan sudah siap pada tab Data Karyawan.
                        </p>
                        <button type="button" @click="activeTab = 'data-karyawan'" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold">
                            <i class="fa-solid fa-arrow-left"></i>
                            Kembali ke Data Karyawan
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
