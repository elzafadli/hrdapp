<x-app-layout>
    @php
        $types = $types ?? $specialLeaveTypes ?? collect();
        $typeOptions = $typeOptions ?? $types->mapWithKeys(function($t) {
            $days = (float) $t->days_allowed;
            $daysLabel = ($days == (int)$days) ? (int)$days : $days;
            return [$t->id => "{$t->name} (Jatah: {$daysLabel} Hari)"];
        })->toArray();
    @endphp
    <div class="py-6" x-data="specialLeaveForm()">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('special-leave.store') }}" method="POST" enctype="multipart/form-data"
                  @submit="if(isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                  class="space-y-5">
                @csrf

                <!-- Top Bar Navigasi -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('special-leave.index') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ajukan Cuti Khusus</h2>
                            <p class="text-xs text-gray-400">Pilih jenis cuti dan tentukan tanggal mulai cuti</p>
                        </div>
                    </div>
                </div>

                @if (session('error'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                        <span>{!! session('error') !!}</span>
                    </div>
                @endif

                <!-- Formulir Input -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">

                    <!-- Pilihan Kategori Cuti Khusus (Dropdown dengan Jatah Hari) -->
                    <div>
                        <x-input-label for="special_leave_type_id" :value="__('Jenis Cuti Khusus')" />
                        <x-select-input id="special_leave_type_id" name="special_leave_type_id" class="mt-1.5 block w-full"
                            :noSelect2="true"
                            :options="$typeOptions"
                            :selected="old('special_leave_type_id', $types->first()?->id)"
                            x-model="selectedTypeId"
                            @change="onTypeSelected()" />
                        <x-input-error class="mt-1.5" :messages="$errors->get('special_leave_type_id')" />

                        <!-- Info Card Keterangan Cuti Terpilih -->
                        <div class="mt-2.5 p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 flex items-start gap-2.5 text-xs text-blue-900">
                            <i class="fa-solid fa-circle-info text-blue-600 mt-0.5 text-sm shrink-0"></i>
                            <div>
                                <span class="font-bold text-blue-900" x-text="currentTypeName"></span>:
                                <span class="text-blue-800" x-text="currentTypeDesc || 'Cuti penting sesuai ketentuan ketenagakerjaan.'"></span>
                                <div class="mt-1 font-semibold text-blue-700">
                                    Durasi Ketentuan: <strong class="text-blue-900" x-text="maxDays + ' Hari kerja'"></strong> (Tidak memotong saldo cuti tahunan).
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tanggal Mulai Cuti (Cukup Input 1 Tanggal Saja) -->
                    <div class="pt-2 border-t border-gray-100 space-y-2">
                        <div>
                            <x-input-label for="start_date" :value="__('Tanggal Mulai Cuti')" />
                            <x-text-input id="start_date" name="start_date" type="text" class="mt-1.5 block w-full bg-white cursor-pointer"
                                placeholder="Pilih tanggal mulai..."
                                x-model="startDate" required />
                            <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                        </div>

                        <div class="p-3 bg-gray-50 border border-gray-200/80 rounded-xl flex items-center gap-2 text-xs text-gray-600">
                            <i class="fa-solid fa-calendar-check text-blue-500 text-sm shrink-0"></i>
                            <span>Tanggal selesai dan durasi hari kerja (<strong x-text="maxDays + ' hari'"></strong>) otomatis dihitung oleh sistem sejak tanggal mulai, melewati libur akhir pekan dan tanggal merah.</span>
                        </div>
                    </div>

                    <!-- Alasan / Keterangan -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <x-input-label for="reason" :value="__('Keterangan / Keperluan')" />
                            <button type="button" @click="document.getElementById('reason').value = 'Mengajukan cuti khusus untuk keperluan ' + currentTypeName + ' sesuai ketentuan yang berlaku.';"
                                class="text-xs text-rose-600 hover:text-rose-800 flex items-center gap-1 font-semibold transition">
                                <i class="fa-regular fa-file-lines text-xs"></i>
                                Gunakan template
                            </button>
                        </div>
                        <textarea id="reason" name="reason" rows="3" required
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Tuliskan keterangan detail cuti khusus...">{{ old('reason') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('reason')" />
                    </div>

                    <!-- Lampiran Berkas Pendukung -->
                    <div class="space-y-2.5 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between p-3.5 border border-dashed border-gray-300 rounded-xl bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-paperclip"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Lampiran Dokumen / Surat Bukti</p>
                                    <p class="text-[11px] text-gray-400" id="fileCountText">Maksimal 10 file @ 3 MB</p>
                                </div>
                            </div>
                            <div>
                                <label for="fileUploadInput" class="cursor-pointer inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
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
                        <a href="{{ route('special-leave.index') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 transition shadow-sm">
                            Batal
                        </a>
                        <button type="submit"
                            x-bind:disabled="isSubmitting"
                            class="inline-flex items-center justify-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                Mengajukan...
                            </span>
                            <span x-show="!isSubmitting">
                                <i class="fa-solid fa-paper-plane text-xs mr-1"></i>
                                Ajukan Cuti Khusus
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        function specialLeaveForm() {
            const typesData = @json($types->keyBy('id'));
            const initialTypeId = '{{ old('special_leave_type_id', $types->first()?->id) }}';
            const initialType = typesData[initialTypeId] || null;

            return {
                types: typesData,
                selectedTypeId: initialTypeId,
                maxDays: initialType ? Number(initialType.days_allowed) : 2,
                currentTypeName: initialType ? initialType.name : '',
                currentTypeDesc: initialType ? initialType.description : '',
                startDate: '{{ old('start_date', '') }}',
                isSubmitting: false,
                fpStart: null,
                disabledDates: @json($disabledDates ?? []),

                init() {
                    this.$nextTick(() => {
                        this.initFlatpickr();
                        const selEl = document.getElementById('special_leave_type_id');
                        if (selEl) {
                            if (selEl.value) {
                                this.selectedTypeId = selEl.value;
                            }
                            this.onTypeSelected();

                            // Sinkronkan bila ada perubahan via DOM / jQuery
                            selEl.addEventListener('change', () => {
                                this.selectedTypeId = selEl.value;
                                this.onTypeSelected();
                            });
                            if (window.jQuery) {
                                window.jQuery(selEl).on('change select2:select', (e) => {
                                    this.selectedTypeId = e.target.value;
                                    this.onTypeSelected();
                                });
                            }
                        } else {
                            this.onTypeSelected();
                        }
                    });
                },

                onTypeSelected() {
                    const selEl = document.getElementById('special_leave_type_id');
                    if (selEl && selEl.value) {
                        this.selectedTypeId = selEl.value;
                    }
                    const current = this.types[this.selectedTypeId];
                    if (current) {
                        this.maxDays = Number(current.days_allowed);
                        this.currentTypeName = current.name;
                        this.currentTypeDesc = current.description;
                    }
                },

                initFlatpickr() {
                    const self = this;
                    if (typeof flatpickr === 'undefined') return;

                    const disabledList = Array.isArray(self.disabledDates) ? self.disabledDates : [];

                    try {
                        const startEl = document.getElementById('start_date');

                        if (startEl) {
                            self.fpStart = flatpickr(startEl, {
                                disableMobile: true,
                                dateFormat: "Y-m-d",
                                altInput: true,
                                altFormat: "l, d F Y",
                                minDate: "today",
                                disable: [
                                    function(date) {
                                        const dStr = flatpickr.formatDate(date, "Y-m-d");
                                        return disabledList.includes(dStr);
                                    }
                                ],
                                onChange: function(selectedDates, dateStr) {
                                    self.startDate = dateStr;
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Flatpickr init error:', e);
                    }
                }
            };
        }
    </script>
</x-app-layout>
