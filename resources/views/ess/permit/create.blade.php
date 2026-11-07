<x-app-layout>
    @php
        $typesData = ($permitTypes ?? collect())->keyBy('id')->map(function($t) {
            return [
                'id' => (int) $t->id,
                'name' => (string) $t->name,
                'code' => (string) $t->code,
                'max_days' => $t->max_days ? (float) $t->max_days : null,
                'is_paid' => (bool) $t->is_paid,
                'requires_attachment' => (bool) $t->requires_attachment,
                'description' => $t->description ?: 'Permohonan izin kerja.',
            ];
        });
    @endphp

    <div class="py-6" x-data="permitForm()">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('permit.store') }}" method="POST" enctype="multipart/form-data"
                @submit="if(isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                class="space-y-5">
                @csrf

                <!-- Top Bar Navigasi -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('permit.index') }}"
                            class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ajukan Izin</h2>
                            <p class="text-xs text-gray-400">Lengkapi formulir permohonan izin kerja di bawah</p>
                        </div>
                    </div>
                </div>

                @if (session('error'))
                    <div
                        class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                        <span>{!! session('error') !!}</span>
                    </div>
                @endif

                <!-- Banner Kategori Izin -->
                <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl shadow-inner border border-amber-100/60 shrink-0">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 text-base" x-text="currentType ? currentType.name : 'Permohonan Izin Kerja'">Permohonan Izin</h3>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="currentType ? currentType.description : 'Pilih jenis izin kerja yang sesuai dengan keperluan Anda.'">
                            Izin meninggalkan pekerjaan karena urusan pribadi, kedinasan, atau keperluan lain.
                        </p>
                    </div>
                </div>

                <!-- Formulir Input -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">

                    <!-- Pilihan Master Jenis Izin (Dengan Select2 Searchable) -->
                    <div class="relative z-30">
                        <x-input-label for="permit_type_id" :value="__('Jenis Izin')" :required="true" />
                        <x-select-input id="permit_type_id" name="permit_type_id" class="mt-1.5 block w-full"
                            :options="$typeOptions ?? []"
                            :selected="old('permit_type_id', $permitTypes->first()?->id ?? '')"
                            x-model="selectedTypeId"
                            @change="onTypeChange()"
                            required />
                        <x-input-error class="mt-1.5" :messages="$errors->get('permit_type_id')" />

                        <!-- Badge Metadata Tipe Izin Terpilih -->
                        <template x-if="currentType">
                            <div class="mt-2.5 p-3 rounded-xl bg-gray-50 border border-gray-200/80 flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 font-semibold px-2 py-0.5 rounded-full"
                                    :class="currentType.is_paid ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                    <i class="fa-solid" :class="currentType.is_paid ? 'fa-coins' : 'fa-file-invoice-dollar'"></i>
                                    <span x-text="currentType.is_paid ? 'Dibayar Penuh (Paid)' : 'Potong Gaji/Cuti (Unpaid)'"></span>
                                </span>

                                <template x-if="currentType.max_days">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 font-semibold">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>Maks. <strong x-text="currentType.max_days"></strong> Hari</span>
                                    </span>
                                </template>

                                <template x-if="currentType.requires_attachment">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-semibold">
                                        <i class="fa-solid fa-paperclip"></i>
                                        <span>Wajib Berkas Bukti</span>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Pilihan Tipe Izin: Sehari Penuh vs Setengah Hari -->
                    <div class="pt-2 border-t border-gray-100">
                        <x-input-label :value="__('Durasi Jam Kerja')" />
                        <div class="grid grid-cols-2 gap-3 mt-1.5">
                            <label
                                class="relative flex items-center justify-center p-3 rounded-xl border cursor-pointer transition select-none text-xs font-semibold"
                                :class="isHalfDay === '0' ? 'border-blue-600 bg-blue-50/60 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="is_half_day" value="0" x-model="isHalfDay" class="sr-only">
                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-calendar-days text-sm"></i>
                                    Sehari Penuh
                                </span>
                            </label>

                            <label
                                class="relative flex items-center justify-center p-3 rounded-xl border cursor-pointer transition select-none text-xs font-semibold"
                                :class="isHalfDay === '1' ? 'border-blue-600 bg-blue-50/60 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
                                <input type="radio" name="is_half_day" value="1" x-model="isHalfDay" class="sr-only">
                                <span class="flex items-center gap-2">
                                    <i class="fa-regular fa-clock text-sm"></i>
                                    Setengah Hari
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Input Tanggal & Waktu -->
                    <div class="pt-2 border-t border-gray-100 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_date"
                                    x-text="isHalfDay === '1' ? 'Tanggal Izin Setengah Hari' : 'Tanggal Mulai Izin'" />
                                <x-text-input id="start_date" name="start_date" type="text"
                                    class="mt-1.5 block w-full bg-white cursor-pointer" placeholder="Pilih tanggal..."
                                    x-model="startDate" @change="onStartDateChange()" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                            </div>

                            <div x-show="isHalfDay === '0'">
                                <x-input-label for="end_date" :value="__('Tanggal Selesai Izin')" />
                                <x-text-input id="end_date" name="end_date" type="text"
                                    class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal selesai..." x-model="endDate" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('end_date')" />
                            </div>

                            <div x-show="isHalfDay === '1'" style="display: none;">
                                <x-input-label for="half_day_type" :value="__('Waktu Shift')" />
                                <x-select-input id="half_day_type" name="half_day_type" class="mt-1.5 block w-full"
                                    :noSelect2="true" :options="[
                                        'first_half' => 'Setengah Hari Pertama (Pagi s/d Siang)',
                                        'second_half' => 'Setengah Hari Kedua (Siang s/d Sore)'
                                    ]"
                                    :selected="old('half_day_type', 'first_half')" x-model="halfDayType" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('half_day_type')" />
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <x-input-label for="reason" :value="__('Keterangan / Keperluan')" :required="true" />
                            <button type="button"
                                @click="useTemplateReason()"
                                class="text-xs text-rose-600 hover:text-rose-800 flex items-center gap-1 font-semibold transition">
                                <i class="fa-regular fa-file-lines text-xs"></i>
                                Gunakan template
                            </button>
                        </div>
                        <textarea id="reason" name="reason" rows="3" required
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Tuliskan alasan izin kerja...">{{ old('reason') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('reason')" />
                    </div>

                    <!-- Lampiran Berkas Sesuai Mockup Mobile -->
                    <div class="space-y-2.5 pt-2 border-t border-gray-100">
                        <div
                            class="p-3 bg-emerald-50/70 border border-emerald-100 text-emerald-800 rounded-xl text-xs flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-emerald-600 text-sm shrink-0"></i>
                            <span>Maksimal 10 file dengan ukuran 3MB per file (PDF, JPG, PNG)</span>
                        </div>

                        <template x-if="currentType && currentType.requires_attachment">
                            <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm shrink-0"></i>
                                <span>Jenis izin ini <strong>wajib melampirkan berkas bukti</strong> (surat tugas/surat dinas/undangan resmi).</span>
                            </div>
                        </template>

                        <div
                            class="flex items-center justify-between p-3.5 border border-dashed border-gray-300 rounded-xl bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-paperclip"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Lampiran Bukti</p>
                                    <p class="text-[11px] text-gray-400" id="fileCountText">0 berkas dipilih</p>
                                </div>
                            </div>
                            <div>
                                <label for="fileUploadInput"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                    Unggah Berkas
                                </label>
                                <input type="file" id="fileUploadInput" name="files[]" multiple class="hidden"
                                    onchange="document.getElementById('fileCountText').textContent = this.files.length + ' berkas dipilih';" />
                            </div>
                        </div>
                        <x-input-error class="mt-1.5" :messages="$errors->get('files')" />
                    </div>

                    <!-- Tombol Aksi di Bawah Kanan Formulir -->
                    <div class="pt-5 border-t border-gray-100 flex items-center justify-end gap-3">
                        <a href="{{ route('permit.index') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 transition shadow-sm">
                            Batal
                        </a>
                        <button type="submit" x-bind:disabled="isSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                Mengajukan...
                            </span>
                            <span x-show="!isSubmitting">
                                <i class="fa-solid fa-paper-plane text-xs mr-1"></i>
                                Ajukan Izin
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        window.PERMIT_TYPES_DATA = @json($typesData);
        console.warn("=== PERMIT_TYPES_DATA (TIPE IZIN) ===", window.PERMIT_TYPES_DATA);
        
        function permitForm() {
            const typesData = window.PERMIT_TYPES_DATA || @json($typesData);
            const initialTypeId = '{{ old('permit_type_id', $permitTypes->first()?->id ?? '') }}';
            const initialType = typesData[initialTypeId] || null;

            return {
                types: typesData,
                selectedTypeId: initialTypeId,
                currentType: initialType,
                isHalfDay: '{{ old('is_half_day', '0') }}',
                halfDayType: '{{ old('half_day_type', 'first_half') }}',
                startDate: '{{ old('start_date', '') }}',
                endDate: '{{ old('end_date', '') }}',
                isSubmitting: false,
                fpStart: null,
                fpEnd: null,
                disabledDates: @json($disabledDates ?? []),

                init() {
                    window.permitFormInstance = this;
                    this.$nextTick(() => {
                        const self = this;

                        // Inisialisasi Flatpickr
                        self.initFlatpickr();

                        // Inisialisasi Select2 Searchable untuk Jenis Izin
                        const selEl = document.getElementById('permit_type_id');
                        if (selEl) {
                            if (selEl.value) {
                                self.selectedTypeId = selEl.value;
                            }
                            self.onTypeChange();

                            selEl.addEventListener('change', () => {
                                self.selectedTypeId = selEl.value;
                                self.onTypeChange();
                            });

                            if (window.jQuery && window.jQuery.fn.select2) {
                                const $sel = window.jQuery(selEl);
                                if (!$sel.data('select2')) {
                                    $sel.select2({
                                        width: '100%',
                                        matcher: typeof window.select2MultiMatcher === 'function' ? window.select2MultiMatcher : undefined
                                    });
                                }

                                $sel.on('change select2:select', function (e) {
                                    self.selectedTypeId = e.target.value || $sel.val();
                                    self.onTypeChange();
                                });
                            }
                        } else {
                            self.onTypeChange();
                        }
                    });
                },

                onTypeChange() {
                    const selEl = document.getElementById('permit_type_id');
                    if (selEl && selEl.value) {
                        this.selectedTypeId = selEl.value;
                    }
                    this.currentType = this.types[this.selectedTypeId] || null;
                },

                useTemplateReason() {
                    const typeName = this.currentType ? this.currentType.name : 'izin';
                    document.getElementById('reason').value = 'Mengajukan permohonan ' + typeName + ' karena keperluan yang tidak dapat ditinggalkan.';
                },

                initFlatpickr() {
                    const self = this;
                    if (typeof flatpickr === 'undefined') return;

                    const disabledList = Array.isArray(self.disabledDates) ? self.disabledDates : [];

                    try {
                        const startEl = document.getElementById('start_date');
                        const endEl = document.getElementById('end_date');

                        if (startEl) {
                            self.fpStart = flatpickr(startEl, {
                                disableMobile: true,
                                dateFormat: "Y-m-d",
                                altInput: true,
                                altFormat: "l, d F Y",
                                disable: [
                                    function (date) {
                                        const dStr = flatpickr.formatDate(date, "Y-m-d");
                                        return disabledList.includes(dStr);
                                    }
                                ],
                                onChange: function (selectedDates, dateStr) {
                                    self.startDate = dateStr;
                                    if (self.isHalfDay === '0' && self.fpEnd) {
                                        self.fpEnd.set('minDate', dateStr || null);
                                        if (self.endDate && dateStr && self.endDate < dateStr) {
                                            self.fpEnd.setDate(dateStr, true);
                                            self.endDate = dateStr;
                                        }
                                    }
                                }
                            });
                        }

                        if (endEl) {
                            self.fpEnd = flatpickr(endEl, {
                                disableMobile: true,
                                dateFormat: "Y-m-d",
                                altInput: true,
                                altFormat: "l, d F Y",
                                minDate: self.startDate || null,
                                disable: [
                                    function (date) {
                                        const dStr = flatpickr.formatDate(date, "Y-m-d");
                                        return disabledList.includes(dStr);
                                    }
                                ],
                                onChange: function (selectedDates, dateStr) {
                                    self.endDate = dateStr;
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Flatpickr init error:', e);
                    }
                },

                onStartDateChange() {
                    if (this.startDate && !this.endDate && this.isHalfDay === '0') {
                        this.endDate = this.startDate;
                        if (this.fpEnd) this.fpEnd.setDate(this.startDate, true);
                    }
                }
            };
        }
    </script>
</x-app-layout>
