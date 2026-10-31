<x-app-layout>
    <div class="py-6" x-data="sickForm()">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('sick.store') }}" method="POST" enctype="multipart/form-data"
                @submit="if(isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                class="space-y-5">
                @csrf

                <!-- Top Bar Navigasi -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('sick.index') }}"
                            class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ajukan Sakit</h2>
                            <p class="text-xs text-gray-400">Lengkapi formulir permohonan izin sakit di bawah</p>
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

                <!-- Banner Kategori Sakit (Sesuai Mockup Thermometer Icon) -->
                <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center gap-3.5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-2xl shadow-inner border border-rose-100/60 shrink-0">
                        <i class="fa-solid fa-temperature-three-quarters"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Sakit</h3>
                        <p class="text-xs text-gray-500">Pengajuan izin tidak masuk kerja karena kondisi
                            sakit/kesehatan.</p>
                    </div>
                </div>

                <!-- Formulir Input -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">

                    <!-- Input Tanggal Izin Sakit Berdasarkan Rentang Tanggal -->
                    <div class="space-y-3">
                        <div
                            class="p-3 bg-gray-50/70 border border-gray-200/80 rounded-xl flex items-center gap-2.5 text-xs text-gray-700 font-semibold">
                            <i class="fa-regular fa-calendar-plus text-base text-gray-500"></i>
                            <span>Pilih tanggal izin sakit berdasarkan rentang tanggal</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_date" :value="__('Tanggal Mulai Sakit')" />
                                <x-text-input id="start_date" name="start_date" type="text"
                                    class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal mulai..." x-model="startDate"
                                    @change="onStartDateChange()" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                            </div>

                            <div>
                                <x-input-label for="end_date" :value="__('Tanggal Selesai Sakit')" />
                                <x-text-input id="end_date" name="end_date" type="text"
                                    class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal selesai..." x-model="endDate" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('end_date')" />
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <x-input-label for="reason" :value="__('Keterangan')" />
                            <button type="button"
                                @click="document.getElementById('reason').value = 'Mengajukan izin tidak masuk kerja dikarenakan kondisi sakit/kurang sehat dan membutuhkan istirahat/pemeriksaan dokter.';"
                                class="text-xs text-rose-600 hover:text-rose-800 flex items-center gap-1 font-semibold transition">
                                <i class="fa-regular fa-file-lines text-xs"></i>
                                Gunakan template
                            </button>
                        </div>
                        <textarea id="reason" name="reason" rows="3" required
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Masukkan keterangan sakit...">{{ old('reason') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('reason')" />
                    </div>

                    <!-- Lampiran Surat Dokter / Resep Obat (Sesuai Mockup Mobile) -->
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
                        <a href="{{ route('sick.index') }}"
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
                                Ajukan Sakit
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        function sickForm() {
            return {
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

                        // Izin sakit memiliki toleransi mundur maksimal 7 hari lalu
                        const minAllowed = new Date();
                        minAllowed.setDate(minAllowed.getDate() - 7);

                        if (startEl) {
                            self.fpStart = flatpickr(startEl, {
                                disableMobile: true,
                                dateFormat: "Y-m-d",
                                altInput: true,
                                altFormat: "l, d F Y",
                                minDate: minAllowed,
                                disable: [
                                    function (date) {
                                        const dStr = flatpickr.formatDate(date, "Y-m-d");
                                        return disabledList.includes(dStr);
                                    }
                                ],
                                onChange: function (selectedDates, dateStr) {
                                    self.startDate = dateStr;
                                    if (self.fpEnd) {
                                        self.fpEnd.set('minDate', dateStr);
                                        if (self.endDate && self.endDate < dateStr) {
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
                                minDate: minAllowed,
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
                    if (this.startDate && !this.endDate) {
                        this.endDate = this.startDate;
                        if (this.fpEnd) this.fpEnd.setDate(this.startDate, true);
                    }
                }
            };
        }
    </script>
</x-app-layout>