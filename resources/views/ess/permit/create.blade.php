<x-app-layout>
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

                <!-- Banner Kategori Izin (Sesuai Mockup Hand Icon) -->
                <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl shadow-inner border border-amber-100/60 shrink-0">
                        <i class="fa-solid fa-hand"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Permohonan Izin</h3>
                        <p class="text-xs text-gray-500">Izin meninggalkan pekerjaan karena urusan pribadi atau
                            keluarga.</p>
                    </div>
                </div>

                <!-- Formulir Input -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">

                    <!-- Pilihan Tipe Izin: Sehari Penuh vs Setengah Hari -->
                    <div>
                        <x-input-label :value="__('Tipe Pengajuan Izin')" />
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
                                <x-input-label for="half_day_type" :value="__('Waktu')" />
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
                            <x-input-label for="reason" :value="__('Keterangan / Keperluan')" />
                            <button type="button"
                                @click="document.getElementById('reason').value = 'Mengajukan permohonan izin tidak masuk kerja karena keperluan pribadi/mendesak.';"
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

                    <!-- Lampiran Berkas Sesuai Mockup Mobile (Maksimal 10 file @ 10 MB) -->
                    <div class="space-y-2.5 pt-2 border-t border-gray-100">
                        <div
                            class="p-3 bg-emerald-50/70 border border-emerald-100 text-emerald-800 rounded-xl text-xs flex items-center gap-2">
                            <i class="fa-solid fa-circle-info text-emerald-600 text-sm shrink-0"></i>
                            <span>Maksimal 10 file dengan ukuran 3MB per file</span>
                        </div>

                        <div
                            class="flex items-center justify-between p-3.5 border border-dashed border-gray-300 rounded-xl bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-paperclip"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Lampiran</p>
                                    <p class="text-[11px] text-gray-400" id="fileCountText">0 berkas</p>
                                </div>
                            </div>
                            <div>
                                <label for="fileUploadInput"
                                    class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                    Unggah
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
        function permitForm() {
            return {
                isHalfDay: '{{ old('is_half_day', '0') }}',
                halfDayType: '{{ old('half_day_type', 'first_half') }}',
                startDate: '{{ old('start_date', '') }}',
                endDate: '{{ old('end_date', '') }}',
                isSubmitting: false,
                fpStart: null,
                fpEnd: null,
                disabledDates: @json($disabledDates ?? []),

                init() {
                    this.$nextTick(() => {
                        this.initFlatpickr();
                    });
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