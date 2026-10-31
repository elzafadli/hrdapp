<x-app-layout>
    <div class="py-6" x-data="overtimeForm()">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('overtime.store') }}" method="POST" enctype="multipart/form-data"
                  @submit="if(isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;"
                  class="space-y-5">
                @csrf

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('overtime.index') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ajukan Lembur</h2>
                            <p class="text-xs text-gray-400">Lengkapi formulir permohonan lembur kerja di bawah</p>
                        </div>
                    </div>
                </div>

                @if (session('error'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                        <span>{!! session('error') !!}</span>
                    </div>
                @endif

                <div class="bg-white rounded-2xl p-4 border border-gray-200 shadow-sm flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl shadow-inner border border-purple-100/60">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Permohonan Lembur Kerja</h3>
                        <p class="text-xs text-gray-500">Kerja lembur di luar jam operasional kerja reguler perusahaan.</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-white rounded-2xl p-5 border border-gray-200 shadow-sm space-y-3">
                    <div x-show="isLoading"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute inset-0 bg-white/85 backdrop-blur-[2px] z-30 flex flex-col items-center justify-center gap-2.5 rounded-2xl select-none"
                        style="display: none;">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-50 text-blue-600 shadow-sm border border-blue-100">
                            <i class="fa-solid fa-circle-notch fa-spin text-lg"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-700 tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-calculator text-blue-500"></i>
                            Menghitung durasi jam lembur...
                        </span>
                    </div>

                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-calculator text-blue-600"></i>
                            Simulasi Durasi Lembur
                        </span>
                        <span x-show="hasConflict" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700" style="display: none;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Jam Bentrok!
                        </span>
                        <span x-show="!hasConflict && totalHours > 0" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800" style="display: none;">
                            Bisa Diajukan
                        </span>
                        <span x-show="!hasConflict && totalHours === 0 && date" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800" style="display: none;">
                            Durasi 0 Jam
                        </span>
                        <span x-show="!hasConflict && !date" class="text-xs px-2.5 py-0.5 rounded-full font-medium text-gray-400">
                            Pilih jadwal lembur di bawah
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="text-[11px] text-gray-400 block font-medium">Tipe Hari</span>
                            <span class="text-xs font-bold text-gray-800 mt-1 block truncate" x-text="dayTypeLabel || 'Hari Kerja'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-purple-50/60 border border-purple-100">
                            <span class="text-[11px] text-purple-600 block font-medium">Total Jam Riil</span>
                            <span class="text-lg font-bold text-purple-700" x-text="totalHours > 0 ? (totalHours.toFixed(2) + ' Jam') : '0.00 Jam'"></span>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="text-[11px] text-gray-400 block font-medium">Istirahat</span>
                            <span class="text-base font-bold text-gray-800 mt-0.5 block" x-text="breakMinutes + ' mnt'"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">
                    <div>
                        <x-input-label for="date" :value="__('Tanggal Lembur')" />
                        <x-text-input id="date" name="date" type="text" class="mt-1.5 block w-full bg-white cursor-pointer"
                            placeholder="Pilih tanggal lembur..."
                            x-model="date"
                            @change="calculate()" />
                        <x-input-error class="mt-1.5" :messages="$errors->get('date')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-gray-100">
                        <div>
                            <x-input-label for="start_time" :value="__('Jam Mulai')" />
                            <x-text-input id="start_time" name="start_time" type="time" class="mt-1.5 block w-full"
                                x-model="startTime"
                                @change="calculate()" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('start_time')" />
                        </div>
                        <div>
                            <x-input-label for="end_time" :value="__('Jam Selesai')" />
                            <x-text-input id="end_time" name="end_time" type="time" class="mt-1.5 block w-full"
                                x-model="endTime"
                                @change="calculate()" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('end_time')" />
                        </div>
                        <div>
                            <x-input-label for="break_minutes" :value="__('Istirahat')" />
                            <x-select-input id="break_minutes" name="break_minutes" class="mt-1.5 block w-full"
                                :noSelect2="true"
                                :options="[
                                    '0' => 'Tanpa Istirahat (0 mnt)',
                                    '30' => '30 Menit',
                                    '60' => '60 Menit (1 Jam)',
                                    '90' => '90 Menit (1.5 Jam)'
                                ]"
                                :selected="old('break_minutes', '0')"
                                x-model="breakMinutes"
                                @change="calculate()" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('break_minutes')" />
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <x-input-label for="reason" :value="__('Alasan / Urgensi Lembur')" />
                            <button type="button" @click="document.getElementById('reason').value = 'Menyelesaikan pekerjaan mendesak yang memiliki tenggat waktu ketat/deadline hari ini.';"
                                class="text-xs text-rose-600 hover:text-rose-800 flex items-center gap-1 font-semibold transition">
                                <i class="fa-regular fa-file-lines text-xs"></i>
                                Gunakan template
                            </button>
                        </div>
                        <textarea id="reason" name="reason" rows="2" required
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Tuliskan urgensi atau alasan lembur...">{{ old('reason') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('reason')" />
                    </div>

                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <x-input-label for="task_description" :value="__('Rincian Pekerjaan / Target Lembur (Opsional)')" />
                        <textarea id="task_description" name="task_description" rows="2"
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Contoh: Rekap laporan bulanan, penyelesaian deploy modul ESS...">{{ old('task_description') }}</textarea>
                    </div>

                    <div class="space-y-2.5 pt-3 border-t border-gray-100">
                        <div class="bg-purple-50/70 border border-purple-200/80 rounded-xl px-4 py-2.5 flex items-center gap-2.5">
                            <i class="fa-solid fa-circle-info text-purple-600 text-sm"></i>
                            <span class="text-xs font-medium text-purple-800">Lampiran SPK Lembur / Foto pendukung (Maksimal 10 file @ 10 MB)</span>
                        </div>
                        <div class="flex items-center justify-between p-3.5 border border-dashed border-gray-300 rounded-xl bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-paperclip"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Lampiran Berkas (Opsional)</p>
                                    <p class="text-[11px] text-gray-400" id="fileCountText">0 berkas</p>
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
                    </div>

                    <div class="pt-5 border-t border-gray-100 flex items-center justify-end gap-3">
                        <a href="{{ route('overtime.index') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 transition shadow-sm">
                            Batal
                        </a>
                        <button type="submit"
                            x-bind:disabled="isSubmitting || isLoading || totalHours <= 0 || hasConflict"
                            class="inline-flex items-center justify-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                Mengajukan...
                            </span>
                            <span x-show="!isSubmitting && isLoading" style="display: none;">
                                <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                                Menghitung...
                            </span>
                            <span x-show="!isSubmitting && !isLoading">
                                <i class="fa-solid fa-paper-plane text-xs mr-1"></i>
                                Ajukan Lembur
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        function overtimeForm() {
            return {
                date: '{{ old('date', date('Y-m-d')) }}',
                startTime: '{{ old('start_time', '17:00') }}',
                endTime: '{{ old('end_time', '20:00') }}',
                breakMinutes: '{{ old('break_minutes', '0') }}',
                totalHours: 0.0,
                dayType: 'workday',
                dayTypeLabel: 'Hari Kerja Biasa',
                hasConflict: false,
                isLoading: false,
                isSubmitting: false,

                init() {
                    this.$nextTick(() => {
                        if (typeof flatpickr !== 'undefined') {
                            flatpickr('#date', {
                                disableMobile: true,
                                locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd F Y',
                                altInputClass: 'mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white cursor-pointer',
                                defaultDate: this.date || 'today',
                                onChange: (selectedDates, dateStr) => {
                                    this.date = dateStr;
                                    this.calculate();
                                }
                            });
                        }
                        this.calculate();
                    });
                },

                async calculate() {
                    if (!this.date || !this.startTime || !this.endTime) return;
                    this.isLoading = true;
                    try {
                        const response = await fetch('{{ route('overtime.calculate-preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                _token: '{{ csrf_token() }}',
                                date: this.date,
                                start_time: this.startTime,
                                end_time: this.endTime,
                                break_minutes: this.breakMinutes
                            })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.totalHours = Number(data.total_hours);
                            this.dayTypeLabel = data.day_type_label;
                            this.hasConflict = Boolean(data.has_conflict);
                        }
                    } catch (e) {
                        console.error('Kalkulasi lembur gagal:', e);
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
