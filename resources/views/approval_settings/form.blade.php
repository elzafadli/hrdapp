<x-app-layout>
    <div class="py-6" x-data="approvalForm()">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Back Button -->
            <div class="flex items-center gap-3">
                <a href="{{ route('approval-settings.index') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                        {{ $isEdit ? 'Edit Alur Persetujuan (Header - Detail)' : 'Tambah Alur Persetujuan Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Konfigurasi tahapan approver berjenjang berdasarkan modul dan jabatan pemohon.</p>
                </div>
            </div>

            <!-- Flash Error Alert -->
            @if (session('error'))
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Form Card -->
            <form method="POST" action="{{ $isEdit ? route('approval-settings.update', $approvalSetting->id) : route('approval-settings.store') }}" @submit="handleSubmit($event)">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="space-y-6">

                    <!-- 1. HEADER SECTION (MODUL & JABATAN PEMOHON) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 font-bold">
                                1
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Header: Modul ESS & Target Jabatan Pemohon</h3>
                                <p class="text-xs text-gray-500">Tentukan modul dan jabatan karyawan yang mengajukan permohonan</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Modul ESS -->
                            <div class="space-y-1.5">
                                <x-input-label for="module" :value="__('Modul ESS')" />
                                <x-select-input id="module" name="module" class="block w-full"
                                    :options="$modules" :selected="old('module', $approvalSetting->module ?? 'all')" :noSelect2="true" required />
                                <span class="text-[11px] text-gray-400 block">Pilih <strong>Semua Modul (All)</strong> agar alur otomatis berlaku untuk seluruh modul ESS tanpa perlu konfigurasi terpisah.</span>
                                <x-input-error class="mt-1" :messages="$errors->get('module')" />
                            </div>

                            <!-- Jabatan Pemohon (Target Karyawan) -->
                            <div class="space-y-1.5">
                                <x-input-label for="requester_jabatan_id" :value="__('Jabatan Pemohon (Target Karyawan)')" />
                                @php
                                    $jabOptions = ['' => '-- Berlaku untuk Semua Jabatan (Default) --'];
                                    foreach($jabatans as $jb) {
                                        $jabOptions[$jb->id] = $jb->jabatan;
                                    }
                                @endphp
                                <x-select-input id="requester_jabatan_id" name="requester_jabatan_id" class="block w-full"
                                    :options="$jabOptions" :selected="old('requester_jabatan_id', $approvalSetting->requester_jabatan_id ?? '')" :noSelect2="true" />
                                <span class="text-[11px] text-gray-400 block">Pilih spesifik jabatan jika alur ini khusus untuk karyawan dengan jabatan tertentu (misal: Kasir).</span>
                                <x-input-error class="mt-1" :messages="$errors->get('requester_jabatan_id')" />
                            </div>

                            <!-- Keterangan Alur -->
                            <div class="space-y-1.5 sm:col-span-2">
                                <x-input-label for="description" :value="__('Keterangan / Catatan Alur')" />
                                <x-text-input id="description" name="description" type="text" class="block w-full"
                                    :value="old('description', $approvalSetting->description)"
                                    placeholder="Contoh: Alur Persetujuan Semua Modul - Khusus Kasir" />
                                <x-input-error class="mt-1" :messages="$errors->get('description')" />
                            </div>

                            <!-- Status Aktif Toggle -->
                            <div class="sm:col-span-2 flex items-center gap-3 pt-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                                        {{ old('is_active', $approvalSetting->is_active ?? true) ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <span class="text-xs font-semibold text-gray-700">Aktifkan Konfigurasi Alur Ini</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. DETAIL SECTION (TABEL TINGKATAN APPROVER DINAMIS) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 font-bold">
                                    2
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Detail: Tahapan Persetujuan Berjenjang (Urutan Approver)</h3>
                                    <p class="text-xs text-gray-500">Susun jabatan approver berurutan dari level 1, level 2, dst.</p>
                                </div>
                            </div>

                            <button type="button" @click="addLevel()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition shrink-0">
                                <i class="fa-solid fa-plus text-xs"></i>
                                Tambah Tahapan Level
                            </button>
                        </div>

                        <!-- Tabel Dinamis Tingkatan Level -->
                        <div class="overflow-x-auto border border-gray-200 rounded-xl">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50/80 text-gray-600 uppercase text-xs font-semibold border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-center w-20">Urutan</th>
                                        <th class="px-4 py-3 w-72">Jabatan Approver</th>
                                        <th class="px-4 py-3 w-36 text-center">Min. Approver</th>
                                        <th class="px-4 py-3">Keterangan / Label Tahap</th>
                                        <th class="px-4 py-3 text-center w-28">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-gray-700">
                                    <template x-for="(lvl, index) in levels" :key="index">
                                        <tr class="hover:bg-blue-50/30 transition">
                                            <!-- Urutan Level Badge -->
                                            <td class="px-4 py-3.5 text-center font-bold">
                                                <input type="hidden" :name="`levels[${index}][level]`" :value="index + 1">
                                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg text-xs font-extrabold bg-blue-100 text-blue-800"
                                                    x-text="`Level ${index + 1}`">
                                                </span>
                                            </td>

                                            <!-- Pilihan Jabatan Approver -->
                                            <td class="px-4 py-3.5">
                                                <select :name="`levels[${index}][jabatan_id]`" x-model="lvl.jabatan_id" required
                                                    class="block w-full text-xs rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-2">
                                                    <template x-for="j in jabatanList" :key="j.id">
                                                        <option :value="j.id" x-text="j.jabatan" :selected="j.id == lvl.jabatan_id"></option>
                                                    </template>
                                                </select>
                                            </td>

                                            <!-- Minimal Approver -->
                                            <td class="px-4 py-3.5 text-center">
                                                <input type="number" :name="`levels[${index}][min_approvers]`" x-model="lvl.min_approvers" min="1" max="10" required
                                                    class="w-20 text-center text-xs rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5 font-bold">
                                            </td>

                                            <!-- Keterangan Level -->
                                            <td class="px-4 py-3.5">
                                                <input type="text" :name="`levels[${index}][description]`" x-model="lvl.description"
                                                    class="w-full text-xs rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-1.5"
                                                    placeholder="Contoh: Persetujuan Atasan Langsung / HRD">
                                            </td>

                                            <!-- Tombol Aksi (Move Up, Move Down, Delete) -->
                                            <td class="px-4 py-3.5 text-center">
                                                <div class="inline-flex items-center gap-1">
                                                    <!-- Move Up -->
                                                    <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                                        class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-20 disabled:hover:bg-transparent transition" title="Naikkan Urutan">
                                                        <i class="fa-solid fa-arrow-up text-xs"></i>
                                                    </button>
                                                    <!-- Move Down -->
                                                    <button type="button" @click="moveDown(index)" :disabled="index === levels.length - 1"
                                                        class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-20 disabled:hover:bg-transparent transition" title="Turunkan Urutan">
                                                        <i class="fa-solid fa-arrow-down text-xs"></i>
                                                    </button>
                                                    <!-- Delete Row -->
                                                    <button type="button" @click="removeLevel(index)" :disabled="levels.length <= 1"
                                                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg disabled:opacity-20 disabled:hover:bg-transparent transition" title="Hapus Tahapan Ini">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Tombol Tambah Level Cepat di Bawah Tabel -->
                        <div class="pt-2 flex justify-start">
                            <button type="button" @click="addLevel()"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                                <i class="fa-solid fa-circle-plus"></i> Tambah Level Selanjutnya
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Aksi Formulir (Batal & Simpan) -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('approval-settings.index') }}"
                            class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 text-xs font-semibold transition">
                            Batal
                        </a>

                        <button type="submit" :disabled="isSubmitting"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-bold text-xs shadow-sm transition">
                            <i class="fa-solid fa-spinner fa-spin" x-show="isSubmitting" style="display: none;"></i>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Alur Persetujuan'"></span>
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    @push('scripts')
        <script>
            function approvalForm() {
                return {
                    jabatanList: @json($jabatans),
                    levels: @json($levels),
                    isSubmitting: false,

                    addLevel() {
                        const nextLevelNum = this.levels.length + 1;
                        const defaultJabId = this.jabatanList.length > 0 ? this.jabatanList[0].id : 1;
                        this.levels.push({
                            level: nextLevelNum,
                            jabatan_id: defaultJabId,
                            min_approvers: 1,
                            description: 'Persetujuan Level ' + nextLevelNum,
                        });
                    },

                    removeLevel(index) {
                        if (this.levels.length <= 1) {
                            alert('Minimal harus ada 1 tingkatan level approval.');
                            return;
                        }
                        this.levels.splice(index, 1);
                    },

                    moveUp(index) {
                        if (index > 0) {
                            const temp = this.levels[index];
                            this.levels[index] = this.levels[index - 1];
                            this.levels[index - 1] = temp;
                        }
                    },

                    moveDown(index) {
                        if (index < this.levels.length - 1) {
                            const temp = this.levels[index];
                            this.levels[index] = this.levels[index + 1];
                            this.levels[index + 1] = temp;
                        }
                    },

                    handleSubmit(event) {
                        if (this.levels.length === 0) {
                            event.preventDefault();
                            alert('Harap tambahkan minimal 1 tingkatan approval.');
                            return;
                        }
                        this.isSubmitting = true;
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
