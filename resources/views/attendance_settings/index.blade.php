<x-app-layout>
    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @endpush

    <div class="py-6" x-data="attendanceSettingsApp()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Messages -->
            @if ($message = Session::get('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl shadow-xs flex items-center justify-between gap-2" role="alert">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                        <span class="font-medium text-xs">{{ $message }}</span>
                    </div>
                    <button type="button" @click="$el.closest('div').remove()" class="text-emerald-500 hover:text-emerald-700">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-xs" role="alert">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-circle-xmark text-red-600"></i>
                        <span class="font-semibold text-xs">Terjadi Kesalahan Validasi:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!$hasCompanyCol)
                <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-xl flex items-start gap-3 shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5 shrink-0"></i>
                    <div class="text-xs space-y-1">
                        <span class="font-bold block">Migrasi Database Diperlukan</span>
                        <span>Kolom <code>company_id</code> dan <code>project_id</code> belum diterapkan pada tabel <code>attendance_settings</code>. Silakan jalankan perintah migrasi secara manual di terminal:</span>
                        <div class="p-2 bg-amber-100/70 rounded font-mono text-[11px] text-amber-950 font-bold select-all">php artisan migrate</div>
                    </div>
                </div>
            @endif

            <!-- Header Halaman -->
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shadow-xs">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Setting Lokasi & Radius Presensi Geolocation</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Atur titik GPS kantor pusat, cabang, atau site project untuk validasi presensi mobile karyawan.</p>
                    </div>
                </div>

                <button type="button" @click="openCreateModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    Tambah Titik Presensi
                </button>
            </div>

            <!-- Kartu Panduan Prioritas Setting Geolocation -->
            <div class="bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-white rounded-2xl border border-blue-100/80 p-5">
                <div class="flex items-center gap-2 text-blue-900 font-bold text-xs mb-2.5">
                    <i class="fa-solid fa-circle-info text-blue-600"></i>
                    Sistem Urutan Prioritas Titik Presensi Karyawan
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    <div class="bg-white/90 p-3 rounded-xl border border-blue-100 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-blue-700">
                            <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold">1</span>
                            Prioritas 1: Company + Project
                        </div>
                        <p class="text-[11px] text-gray-500">Khusus karyawan yang ditugaskan pada Proyek tertentu di Perusahaan terkait. Presensi tervalidasi di titik lokasi proyek tersebut.</p>
                    </div>

                    <div class="bg-white/90 p-3 rounded-xl border border-blue-100 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-indigo-700">
                            <span class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center font-bold">2</span>
                            Prioritas 2: Company Saja
                        </div>
                        <p class="text-[11px] text-gray-500">Karyawan pada Perusahaan terkait yang tidak ditugaskan di proyek khusus (misal staf kantor pusat atau cabang umum).</p>
                    </div>

                    <div class="bg-white/90 p-3 rounded-xl border border-blue-100 space-y-1">
                        <div class="flex items-center gap-1.5 font-bold text-gray-700">
                            <span class="w-5 h-5 rounded-full bg-gray-500 text-white text-[10px] flex items-center justify-center font-bold">3</span>
                            Prioritas 3: Global / Fallback
                        </div>
                        <p class="text-[11px] text-gray-500">Titik acuan umum jika karyawan belum memiliki aturan khusus pada Perusahaan atau Proyeknya.</p>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Titik Lokasi Presensi -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Daftar Titik Lokasi Presensi Terdaftar</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Total {{ $settings->count() }} lokasi presensi aktif.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/75 text-gray-500 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3.5 whitespace-nowrap">Tingkat Prioritas</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Nama Titik / Kantor</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Perusahaan (Company)</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Proyek (Project)</th>
                                <th class="px-6 py-3.5 text-center whitespace-nowrap">Radius Toleransi</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Luar Radius</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Koordinat GPS</th>
                                <th class="px-6 py-3.5 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($settings as $st)
                                @php
                                    $isPriority1 = $hasCompanyCol && !empty($st->company_id) && !empty($st->project_id);
                                    $isPriority2 = $hasCompanyCol && !empty($st->company_id) && empty($st->project_id);
                                    $isPriority3 = !$hasCompanyCol || empty($st->company_id);
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($isPriority1)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                                <i class="fa-solid fa-crown text-[10px]"></i> Prioritas 1 (Project)
                                            </span>
                                        @elseif($isPriority2)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                                <i class="fa-solid fa-building text-[10px]"></i> Prioritas 2 (Company)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                <i class="fa-solid fa-globe text-[10px]"></i> Prioritas 3 (Global)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900 text-xs whitespace-nowrap">
                                        {{ $st->office_name }}
                                    </td>
                                    <td class="px-6 py-4 text-xs whitespace-nowrap">
                                        @if($st->company)
                                            <span class="font-semibold text-gray-800">{{ $st->company->name }}</span>
                                        @else
                                            <span class="text-gray-400 italic">Semua Perusahaan (Global)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs whitespace-nowrap">
                                        @if($st->project)
                                            <span class="inline-flex items-center gap-1 font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                                                <i class="fa-solid fa-diagram-project text-[10px]"></i>
                                                {{ $st->project->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Semua Proyek / Kantor</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="font-bold text-blue-600 font-mono text-xs bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                                            {{ $st->radius_meters }} m
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($st->allow_outside_radius)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                                                <i class="fa-solid fa-circle-check text-[9px]"></i> Diizinkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800">
                                                <i class="fa-solid fa-circle-xmark text-[9px]"></i> Wajib Radius
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-[11px] text-gray-500 whitespace-nowrap">
                                        {{ number_format($st->latitude, 5) }}, {{ number_format($st->longitude, 5) }}
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="openEditModal({{ json_encode($st) }})"
                                                class="p-2 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition" title="Edit Pengaturan">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>

                                            <form action="{{ route('attendance-settings.destroy', $st->id) }}" method="POST"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus titik lokasi {{ $st->office_name }}?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition" title="Hapus Pengaturan">
                                                    <i class="fa-solid fa-trash text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-map-location-dot text-4xl text-gray-300"></i>
                                            <p class="text-sm">Belum ada titik lokasi presensi yang terdaftar.</p>
                                            <button type="button" @click="openCreateModal()" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                                                + Tambah Titik Presensi Sekarang
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Form Tambah / Edit Titik Lokasi Presensi -->
            <div x-show="isModalOpen" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
                 style="display: none;">
                
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="closeModal()"></div>

                <!-- Modal Content Card -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transform transition-all w-full max-w-4xl z-10 my-8">
                    <form :action="formAction" method="POST" @submit="isSubmitting = true">
                        @csrf
                        <template x-if="isEditMode">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <input type="hidden" name="id" :value="form.id">

                        <!-- Modal Header -->
                        <div class="p-6 bg-gradient-to-r from-gray-50 to-blue-50/30 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
                                    <i class="fa-solid fa-map-pin text-base"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900" x-text="isEditMode ? 'Edit Titik Presensi & Radius' : 'Tambah Titik Presensi & Radius'"></h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Atur penugasan kantor, perusahaan, proyek, dan koordinat GPS.</p>
                                </div>
                            </div>
                            <button type="button" @click="closeModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl transition">
                                <i class="fa-solid fa-xmark text-base"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                
                                <!-- Kolom Kiri: Form Fields -->
                                <div class="lg:col-span-5 space-y-4">
                                    
                                    <!-- Nama Kantor / Titik -->
                                    <div>
                                        <x-input-label for="office_name" :value="__('Nama Kantor / Titik Lokasi')" :required="true" />
                                        <x-text-input id="office_name" name="office_name" type="text"
                                            x-model="form.office_name"
                                            placeholder="Contoh: Head Office Jakarta, Site Project Bontang" class="mt-1 block w-full text-xs" required />
                                        <x-input-error class="mt-1" :messages="$errors->get('office_name')" />
                                    </div>

                                    <!-- Pilih Perusahaan (Company) -->
                                    <div>
                                        <x-input-label for="company_id" :value="__('Perusahaan (Company)')" />
                                        <select id="company_id" name="company_id" x-model="form.company_id" @change="onCompanyChange()"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-xs">
                                            <option value="">-- Semua Perusahaan (Global / Fallback) --</option>
                                            @foreach($companies as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-gray-400 mt-1">Kosongkan jika berlaku global untuk seluruh anak perusahaan.</p>
                                    </div>

                                    <!-- Pilih Proyek (Project) -->
                                    <div>
                                        <x-input-label for="project_id" :value="__('Proyek (Project)')" />
                                        <select id="project_id" name="project_id" x-model="form.project_id"
                                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-xs">
                                            <option value="">-- Semua Proyek (Kantor Pusat / Cabang) --</option>
                                            <template x-for="proj in filteredProjects" :key="proj.id">
                                                <option :value="proj.id" x-text="proj.name" :selected="form.project_id == proj.id"></option>
                                            </template>
                                        </select>
                                        <p class="text-[11px] text-gray-400 mt-1">Pilih jika titik lokasi ini khusus untuk site proyek tertentu (Prioritas 1).</p>
                                    </div>

                                    <!-- Input Koordinat GPS -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <x-input-label for="latitude" :value="__('Latitude')" :required="true" />
                                            <x-text-input id="latitude" name="latitude" type="text"
                                                x-model="form.latitude" @input="onCoordInputChange()"
                                                class="mt-1 block w-full font-mono text-xs" required />
                                        </div>
                                        <div>
                                            <x-input-label for="longitude" :value="__('Longitude')" :required="true" />
                                            <x-text-input id="longitude" name="longitude" type="text"
                                                x-model="form.longitude" @input="onCoordInputChange()"
                                                class="mt-1 block w-full font-mono text-xs" required />
                                        </div>
                                    </div>

                                    <!-- Tombol Ambil Koordinat Saya -->
                                    <div>
                                        <button type="button" @click="getCurrentLocation()"
                                            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                                            <i class="fa-solid fa-crosshairs text-blue-600"></i>
                                            Ambil Titik Koordinat Saya Saat Ini
                                        </button>
                                    </div>

                                    <!-- Batas Radius -->
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <x-input-label for="radius_meters" :value="__('Batas Radius Presensi (Meter)')" :required="true" />
                                            <span class="text-xs font-bold text-blue-600 font-mono" x-text="form.radius_meters + ' m'"></span>
                                        </div>
                                        <x-text-input id="radius_meters" name="radius_meters" type="number" min="10" max="50000"
                                            x-model="form.radius_meters" @input="onRadiusInputChange()"
                                            class="mt-1 block w-full text-xs font-mono" required />
                                        <p class="text-[11px] text-gray-400 mt-1">Toleransi radius maksimal dari titik GPS (misal 100 - 500 meter).</p>
                                    </div>

                                    <!-- Izinkan Luar Radius -->
                                    <div class="pt-2 border-t border-gray-100">
                                        <label class="flex items-start gap-2.5 cursor-pointer">
                                            <input type="checkbox" id="allow_outside_radius" name="allow_outside_radius" value="1"
                                                x-model="form.allow_outside_radius"
                                                class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                            <div>
                                                <span class="text-xs font-semibold text-gray-800">Izinkan Check-in di Luar Radius</span>
                                                <p class="text-[11px] text-gray-500 mt-0.5">Jika dicentang, presensi tetap diterima dengan peringatan status <i>Luar Radius</i>.</p>
                                            </div>
                                        </label>
                                    </div>

                                </div>

                                <!-- Kolom Kanan: Interactive Leaflet Map Picker -->
                                <div class="lg:col-span-7 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-700">Preview Peta & Radius</span>
                                        <span class="text-[11px] text-gray-400">Klik atau geser pin merah pada peta</span>
                                    </div>
                                    <div id="settingsModalMap" class="w-full h-[360px] rounded-xl border border-gray-200 z-10 shadow-xs"></div>
                                </div>

                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="closeModal()"
                                class="px-4 py-2 bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-50">
                                <i class="fa-solid fa-floppy-disk text-xs"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : (isEditMode ? 'Simpan Perubahan' : 'Simpan Titik Baru')"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            function attendanceSettingsApp() {
                return {
                    isModalOpen: false,
                    isEditMode: false,
                    isSubmitting: false,
                    formAction: "{{ route('attendance-settings.store') }}",
                    allProjects: @json($projects),
                    filteredProjects: [],
                    
                    form: {
                        id: null,
                        company_id: '',
                        project_id: '',
                        office_name: '',
                        latitude: -6.2000000,
                        longitude: 106.8166660,
                        radius_meters: 150,
                        allow_outside_radius: true,
                    },

                    map: null,
                    marker: null,
                    circle: null,

                    init() {
                        this.filterProjects();
                    },

                    filterProjects() {
                        if (!this.form.company_id) {
                            this.filteredProjects = this.allProjects;
                        } else {
                            this.filteredProjects = this.allProjects.filter(p => p.company_id == this.form.company_id);
                        }
                    },

                    onCompanyChange() {
                        this.filterProjects();
                        // Jika project yang dipilih sebelumnya tidak ada di company baru, reset project_id
                        const exists = this.filteredProjects.some(p => p.id == this.form.project_id);
                        if (!exists) {
                            this.form.project_id = '';
                        }
                    },

                    openCreateModal() {
                        this.isEditMode = false;
                        this.formAction = "{{ route('attendance-settings.store') }}";
                        this.form = {
                            id: null,
                            company_id: '',
                            project_id: '',
                            office_name: '',
                            latitude: -6.2000000,
                            longitude: 106.8166660,
                            radius_meters: 150,
                            allow_outside_radius: true,
                        };
                        this.filterProjects();
                        this.isModalOpen = true;
                        this.$nextTick(() => {
                            this.initOrUpdateMap();
                        });
                    },

                    openEditModal(setting) {
                        this.isEditMode = true;
                        this.formAction = "{{ url('attendance-settings/update') }}/" + setting.id;
                        this.form = {
                            id: setting.id,
                            company_id: setting.company_id || '',
                            project_id: setting.project_id || '',
                            office_name: setting.office_name,
                            latitude: parseFloat(setting.latitude),
                            longitude: parseFloat(setting.longitude),
                            radius_meters: parseInt(setting.radius_meters) || 150,
                            allow_outside_radius: Boolean(setting.allow_outside_radius),
                        };
                        this.filterProjects();
                        this.isModalOpen = true;
                        this.$nextTick(() => {
                            this.initOrUpdateMap();
                        });
                    },

                    closeModal() {
                        this.isModalOpen = false;
                    },

                    initOrUpdateMap() {
                        const lat = parseFloat(this.form.latitude) || -6.2000000;
                        const lng = parseFloat(this.form.longitude) || 106.8166660;
                        const radius = parseInt(this.form.radius_meters) || 150;

                        setTimeout(() => {
                            const mapEl = document.getElementById('settingsModalMap');
                            if (!mapEl) return;

                            if (!this.map) {
                                this.map = L.map('settingsModalMap').setView([lat, lng], 16);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; OpenStreetMap contributors'
                                }).addTo(this.map);

                                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                                this.circle = L.circle([lat, lng], {
                                    color: '#2563eb',
                                    fillColor: '#3b82f6',
                                    fillOpacity: 0.2,
                                    radius: radius
                                }).addTo(this.map);

                                this.marker.on('dragend', (e) => {
                                    const pos = this.marker.getLatLng();
                                    this.updateCoords(pos.lat, pos.lng);
                                });

                                this.map.on('click', (e) => {
                                    this.updateCoords(e.latlng.lat, e.latlng.lng);
                                });
                            } else {
                                this.map.invalidateSize();
                                this.map.setView([lat, lng], 16);
                                this.marker.setLatLng([lat, lng]);
                                this.circle.setLatLng([lat, lng]);
                                this.circle.setRadius(radius);
                            }
                        }, 250);
                    },

                    updateCoords(lat, lng) {
                        this.form.latitude = lat.toFixed(7);
                        this.form.longitude = lng.toFixed(7);
                        if (this.marker) this.marker.setLatLng([lat, lng]);
                        if (this.circle) this.circle.setLatLng([lat, lng]);
                    },

                    onCoordInputChange() {
                        const lat = parseFloat(this.form.latitude);
                        const lng = parseFloat(this.form.longitude);
                        if (!isNaN(lat) && !isNaN(lng) && this.marker && this.circle && this.map) {
                            this.marker.setLatLng([lat, lng]);
                            this.circle.setLatLng([lat, lng]);
                            this.map.setView([lat, lng]);
                        }
                    },

                    onRadiusInputChange() {
                        const r = parseInt(this.form.radius_meters) || 0;
                        if (this.circle) {
                            this.circle.setRadius(r);
                        }
                    },

                    getCurrentLocation() {
                        if (!navigator.geolocation) {
                            alert('Geolocation tidak didukung oleh browser Anda.');
                            return;
                        }

                        navigator.geolocation.getCurrentPosition((pos) => {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            this.updateCoords(lat, lng);
                            if (this.map) {
                                this.map.setView([lat, lng], 17);
                            }
                        }, (err) => {
                            alert('Gagal mengambil titik GPS: ' + err.message);
                        }, { enableHighAccuracy: true });
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
