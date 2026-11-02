<x-app-layout>
    @php
        $specialTypesJson = ($specialLeaveTypes ?? collect())->map(function($t) {
            return [
                'id' => (string) $t->id,
                'name' => $t->name,
                'days_allowed' => (float) $t->days_allowed,
                'description' => $t->description ?? '',
            ];
        })->values();
    @endphp

    <div class="py-6" x-data="leaveForm()">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" 
                  @submit="if(isSubmitting) { $event.preventDefault(); return false; } isSubmitting = true;" 
                  class="space-y-5">
                @csrf
                <input type="hidden" name="leave_category" :value="leaveCategory">

                <!-- Top Bar Navigasi -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('leave.index') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                            <i class="fa-solid fa-arrow-left text-lg"></i>
                        </a>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Ajukan Cuti</h2>
                            <p class="text-xs text-gray-400">Pilih jenis cuti dan lengkapi formulir permohonan di bawah</p>
                        </div>
                    </div>
                </div>

                <!-- Alert Error -->
                @if (session('error'))
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                        <span>{!! session('error') !!}</span>
                    </div>
                @endif

                <!-- Ketentuan Pengajuan H-1 -->
                <div class="bg-amber-50/80 border border-amber-200/80 rounded-2xl p-4 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center text-white shrink-0 mt-0.5">
                        <i class="fa-regular fa-calendar text-sm"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-amber-950 text-sm">Ketentuan pengajuan</h4>
                        <p class="text-xs text-amber-800 mt-0.5">Paling lambat 1 hari sebelum tanggal cuti (H-1). Tidak dapat memilih tanggal lampau (back-date).</p>
                    </div>
                </div>

                <!-- Simulasi & Sisa Cuti (Live Reactive Box) -->
                <div class="relative overflow-hidden bg-white rounded-2xl p-5 border border-gray-200 shadow-sm space-y-3">
                    <!-- Loading Block & Unlock Overlay -->
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
                            Menghitung simulasi cuti...
                        </span>
                    </div>

                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-calculator" :class="leaveCategory === 'annual' ? 'text-blue-600' : 'text-purple-600'"></i>
                            <span x-text="leaveCategory === 'annual' ? 'Simulasi Sisa Cuti Tahunan' : 'Simulasi Cuti Khusus'"></span>
                        </span>
                        <span x-show="isBackDate" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">
                            <i class="fa-solid fa-ban"></i> Back-date Dilarang!
                        </span>
                        <span x-show="!isBackDate && hasConflict" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">
                            <i class="fa-solid fa-triangle-exclamation"></i> Tanggal Bentrok!
                        </span>
                        <span x-show="!isBackDate && !hasConflict && leaveCategory === 'annual' && isExceeded" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700">
                            Saldo Kurang!
                        </span>
                        <span x-show="!isBackDate && !hasConflict && ((leaveCategory === 'annual' && !isExceeded && deduction > 0) || (leaveCategory === 'special' && specialDaysCount > 0))" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800">
                            Bisa Diajukan
                        </span>
                        <span x-show="!isBackDate && !hasConflict && ((leaveCategory === 'annual' && !isExceeded && deduction === 0 && startDate) || (leaveCategory === 'special' && specialDaysCount === 0 && startDate))" class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-amber-100 text-amber-800">
                            Hari Libur / Off (0 Hari)
                        </span>
                        <span x-show="!isBackDate && !hasConflict && !startDate" class="text-xs px-2.5 py-0.5 rounded-full font-medium text-gray-400">
                            Pilih tanggal di bawah
                        </span>
                    </div>

                    <!-- KONTEN SIMULASI JIKA CUTI TAHUNAN -->
                    <template x-if="leaveCategory === 'annual'">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                                <span class="text-[11px] text-gray-400 block font-medium">Jatah Tersedia</span>
                                <span class="text-lg font-bold text-gray-800" x-text="currentRemaining.toFixed(1)"></span>
                                <span class="text-[11px] text-gray-400">Hari</span>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100">
                                <span class="text-[11px] text-blue-600 block font-medium">Diajukan</span>
                                <span class="text-lg font-bold text-blue-700" x-text="deduction > 0 ? ('-' + deduction.toFixed(1)) : '0.0'"></span>
                                <span class="text-[11px] text-blue-500">Hari</span>
                            </div>
                            <div class="p-3 rounded-xl border transition" :class="isExceeded ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50/60 border-emerald-100 text-emerald-800'">
                                <span class="text-[11px] block font-medium" :class="isExceeded ? 'text-rose-600' : 'text-emerald-700'">Sisa Nanti</span>
                                <span class="text-lg font-bold" x-text="remainingAfter.toFixed(1)"></span>
                                <span class="text-[11px]">Hari</span>
                            </div>
                        </div>
                    </template>

                    <!-- KONTEN SIMULASI JIKA CUTI KHUSUS -->
                    <template x-if="leaveCategory === 'special'">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="p-3 rounded-xl bg-purple-50/60 border border-purple-100">
                                <span class="text-[11px] text-purple-700 block font-medium">Jatah Cuti Khusus</span>
                                <span class="text-lg font-bold text-purple-900" x-text="currentSpecialMaxDays.toFixed(1)"></span>
                                <span class="text-[11px] text-purple-600">Hari Kerja</span>
                            </div>
                            <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100">
                                <span class="text-[11px] text-blue-600 block font-medium">Durasi Hari Riil</span>
                                <span class="text-lg font-bold text-blue-700" x-text="specialDaysCount.toFixed(1)"></span>
                                <span class="text-[11px] text-blue-500">Hari</span>
                            </div>
                            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100 text-emerald-800">
                                <span class="text-[11px] text-emerald-700 block font-medium">Potong Kuota Tahunan</span>
                                <span class="text-lg font-bold text-emerald-800">0.0</span>
                                <span class="text-[11px] text-emerald-600">Hari (Gratis)</span>
                            </div>
                        </div>
                    </template>

                    <!-- Info Cuti Khusus Tidak Memotong Kuota -->
                    <div x-show="leaveCategory === 'special'" class="text-[11px] text-purple-800 bg-purple-50 px-3 py-1.5 rounded-lg border border-purple-100 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <i class="fa-solid fa-gift text-purple-600"></i>
                            <span>Cuti khusus diberikan tanpa memotong saldo kuota cuti tahunan Anda.</span>
                        </span>
                        <span x-show="specialEndDateFormatted" class="font-semibold text-purple-900">
                            Selesai: <span x-text="specialEndDateFormatted"></span>
                        </span>
                    </div>

                    <!-- Notifikasi Khusus Jika Memilih Back-Date -->
                    <div x-show="isBackDate" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-ban text-rose-500 text-base shrink-0"></i>
                        <span x-text="backDateMessage"></span>
                    </div>

                    <!-- Notifikasi Khusus Jika Memilih Tanggal Bentrok -->
                    <div x-show="!isBackDate && hasConflict" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-rose-700">
                            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                            Tanggal Sudah Pernah Diajukan Sebelumnya:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-rose-700 font-medium">
                            <template x-for="c in conflicts" :key="c.date">
                                <li x-text="c.message"></li>
                            </template>
                        </ul>
                        <p class="text-[11px] text-rose-600/80 pt-1">
                            Anda tidak dapat mengajukan cuti pada tanggal yang sama dengan pengajuan cuti yang sudah ada.
                        </p>
                    </div>

                    <!-- Notifikasi Khusus Jika Memilih Hari Libur / Weekend -->
                    <div x-show="!isBackDate && !hasConflict && ((leaveCategory === 'annual' && deduction === 0) || (leaveCategory === 'special' && specialDaysCount === 0)) && skippedDates.length > 0" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-amber-500 text-sm shrink-0"></i>
                        <span>Tanggal yang Anda pilih adalah <strong>hari libur/akhir pekan</strong> sesuai jadwal kerja (Sabtu/Minggu), sehingga <strong>0 hari kerja</strong> yang terhitung.</span>
                    </div>

                    <!-- Breakdown Tanggal Kerja vs Libur -->
                    <div x-show="workingDays.length > 0 || skippedDates.length > 0" class="pt-2 text-xs space-y-1.5 border-t border-gray-100">
                        <div class="text-[11px] text-gray-400 font-semibold uppercase">Rincian Hari Kalender:</div>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="day in workingDays" :key="day.date">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-medium text-[11px] border border-blue-100">
                                    <i class="fa-solid fa-check text-[9px] text-blue-500"></i>
                                    <span x-text="day.day_name + ', ' + day.formatted + ' (-' + day.val + ')'"></span>
                                </span>
                            </template>
                            <template x-for="skip in skippedDates" :key="skip.date">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 text-gray-400 text-[11px] line-through" :title="skip.reason">
                                    <i class="fa-solid fa-ban text-[9px]"></i>
                                    <span x-text="skip.day_name + ', ' + skip.formatted"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Card Form Input -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">

                    <!-- STEP 1: Radio Button Pilihan Kategori Cuti (Default: Cuti Tahunan) -->
                    <div>
                        <x-input-label :value="__('Pilih Kategori Cuti')" class="mb-2 font-bold text-gray-900" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition"
                                :class="leaveCategory === 'annual' ? 'border-blue-600 bg-blue-50/40 text-blue-900 shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                                <input type="radio" name="leave_category_radio" value="annual" x-model="leaveCategory" @change="onCategoryChange()" class="text-blue-600 focus:ring-blue-500">
                                <div>
                                    <div class="font-bold text-sm flex items-center gap-1.5">
                                        <i class="fa-solid fa-umbrella-beach text-blue-600"></i>
                                        <span>Cuti Tahunan</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Memotong kuota saldo cuti tahunan</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3.5 border-2 rounded-xl cursor-pointer transition"
                                :class="leaveCategory === 'special' ? 'border-purple-600 bg-purple-50/40 text-purple-900 shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                                <input type="radio" name="leave_category_radio" value="special" x-model="leaveCategory" @change="onCategoryChange()" class="text-purple-600 focus:ring-purple-500">
                                <div>
                                    <div class="font-bold text-sm flex items-center gap-1.5">
                                        <i class="fa-solid fa-award text-purple-600"></i>
                                        <span>Cuti Khusus</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">Menikah, duka, melahirkan, dll</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 2A: FORM CUTI TAHUNAN -->
                    <div x-show="leaveCategory === 'annual'" class="space-y-4 pt-3 border-t border-gray-100">
                        <!-- Pilihan Durasi Cuti Tahunan -->
                        <div>
                            <x-input-label :value="__('Durasi Cuti Tahunan')" class="mb-2 font-bold text-gray-900" />
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition"
                                    :class="isHalfDay === '0' ? 'border-blue-600 bg-blue-50/40 text-blue-900 shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                                    <input type="radio" name="is_half_day" value="0" x-model="isHalfDay" @change="onTypeChange()" class="text-blue-600 focus:ring-blue-500">
                                    <div>
                                        <div class="font-bold text-sm">Sehari Penuh</div>
                                        <div class="text-[11px] text-gray-500">1.0 hari kerja</div>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer transition"
                                    :class="isHalfDay === '1' ? 'border-amber-500 bg-amber-50/40 text-amber-900 shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                                    <input type="radio" name="is_half_day" value="1" x-model="isHalfDay" @change="onTypeChange()" class="text-amber-600 focus:ring-amber-500">
                                    <div>
                                        <div class="font-bold text-sm">Setengah Hari</div>
                                        <div class="text-[11px] text-gray-500">0.5 hari kerja</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Tanggal Cuti Tahunan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="start_date" x-text="isHalfDay === '1' ? 'Tanggal Cuti Setengah Hari' : 'Tanggal Mulai Cuti'" />
                                <x-text-input id="start_date" name="start_date" type="text" class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal..."
                                    x-model="startDate"
                                    x-bind:disabled="leaveCategory !== 'annual'"
                                    @input="onStartDateChange()"
                                    @change="onStartDateChange()" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                            </div>

                            <!-- Tanggal Selesai (Sehari Penuh) -->
                            <div x-show="isHalfDay === '0'">
                                <x-input-label for="end_date" :value="__('Tanggal Selesai Cuti')" />
                                <x-text-input id="end_date" name="end_date" type="text" class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal selesai..."
                                    x-model="endDate"
                                    x-bind:disabled="leaveCategory !== 'annual'"
                                    @input="onEndDateChange()"
                                    @change="onEndDateChange()" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('end_date')" />
                            </div>

                            <!-- Pilihan Waktu (Setengah Hari) -->
                            <div x-show="isHalfDay === '1'" style="display: none;">
                                <x-input-label for="half_day_type" :value="__('Waktu')" />
                                <x-select-input id="half_day_type" name="half_day_type" class="mt-1.5 block w-full"
                                    :noSelect2="true"
                                    :options="[
                                        'first_half' => 'Setengah Hari Pertama (Pagi s/d Siang)',
                                        'second_half' => 'Setengah Hari Kedua (Siang s/d Sore)'
                                    ]"
                                    :selected="old('half_day_type', 'first_half')"
                                    x-model="halfDayType"
                                    @change="calculate()" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('half_day_type')" />
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2B: FORM CUTI KHUSUS -->
                    <div x-show="leaveCategory === 'special'" style="display: none;" class="space-y-4 pt-3 border-t border-gray-100">
                        <!-- Dropdown Jenis Cuti Khusus -->
                        <div>
                            <x-input-label for="special_leave_type_id" :value="__('Jenis Cuti Khusus')" />
                            <x-select-input id="special_leave_type_id" name="special_leave_type_id" class="mt-1.5 block w-full"
                                :noSelect2="true"
                                :options="$typeOptions ?? []"
                                :selected="old('special_leave_type_id', $specialLeaveTypes->first()?->id)"
                                x-model="selectedTypeId"
                                @change="onSpecialTypeSelected()" />
                            <x-input-error class="mt-1.5" :messages="$errors->get('special_leave_type_id')" />

                            <!-- Info Card Keterangan Cuti Khusus Terpilih -->
                            <div class="mt-2.5 p-3.5 rounded-xl bg-purple-50/70 border border-purple-100 flex items-start gap-2.5 text-xs text-purple-900">
                                <i class="fa-solid fa-circle-info text-purple-600 mt-0.5 text-sm shrink-0"></i>
                                <div>
                                    <span class="font-bold text-purple-950" x-text="currentSpecialTypeName"></span>:
                                    <span class="text-purple-800" x-text="currentSpecialTypeDesc || 'Cuti penting sesuai ketentuan ketenagakerjaan.'"></span>
                                    <div class="mt-1 font-semibold text-purple-700">
                                        Ketentuan Jatah: <strong class="text-purple-950" x-text="currentSpecialMaxDays + ' Hari kerja'"></strong> (Tidak memotong kuota tahunan).
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tanggal Mulai Cuti Khusus -->
                        <div class="space-y-2">
                            <div>
                                <x-input-label for="special_start_date" :value="__('Tanggal Mulai Cuti Khusus')" />
                                <x-text-input id="special_start_date" name="start_date" type="text" class="mt-1.5 block w-full bg-white cursor-pointer"
                                    placeholder="Pilih tanggal mulai..."
                                    x-model="startDate"
                                    x-bind:disabled="leaveCategory !== 'special'"
                                    @input="onStartDateChange()"
                                    @change="onStartDateChange()" />
                                <x-input-error class="mt-1.5" :messages="$errors->get('start_date')" />
                            </div>

                            <div class="p-3 bg-gray-50 border border-gray-200/80 rounded-xl flex items-center gap-2 text-xs text-gray-600">
                                <i class="fa-solid fa-calendar-check text-purple-500 text-sm shrink-0"></i>
                                <span>Tanggal selesai dan durasi hari kerja (<strong x-text="currentSpecialMaxDays + ' hari'"></strong>) otomatis dihitung oleh sistem sejak tanggal mulai, melewati libur akhir pekan dan tanggal merah.</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-500 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-info text-blue-500"></i>
                        <span>Tanggal lampau dan tanggal cuti yang sudah pernah diajukan otomatis <strong>dinonaktifkan (dicoret)</strong> pada kalender.</span>
                    </p>

                    <!-- STEP 3: Keterangan / Keperluan -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <x-input-label for="reason" :value="__('Keterangan / Keperluan')" />
                            <button type="button" @click="useTemplateReason()"
                                class="text-xs text-rose-600 hover:text-rose-800 flex items-center gap-1 font-semibold transition">
                                <i class="fa-solid fa-file-lines text-xs"></i>
                                Gunakan template
                            </button>
                        </div>
                        <textarea id="reason" name="reason" rows="3" required
                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm placeholder-gray-400"
                            placeholder="Tuliskan keterangan permohonan cuti...">{{ old('reason') }}</textarea>
                        <x-input-error class="mt-1.5" :messages="$errors->get('reason')" />
                    </div>

                    <!-- STEP 4: Lampiran -->
                    <div class="space-y-2.5 pt-2 border-t border-gray-100">
                        <div class="flex items-center justify-between p-3.5 border border-dashed border-gray-300 rounded-xl bg-gray-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="fa-solid fa-paperclip text-base"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Lampiran Dokumen (Opsional)</p>
                                    <p class="text-[11px] text-gray-400" id="fileCountText">Maks 10 file @ 3 MB</p>
                                </div>
                            </div>
                            <div>
                                <label for="fileUploadInput" class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                                    Unggah
                                </label>
                                <input type="file" id="fileUploadInput" name="files[]" multiple class="hidden"
                                    onchange="
                                        let oversized = [];
                                        for (let f of this.files) {
                                            if (f.size > 3 * 1024 * 1024) oversized.push(f.name);
                                        }
                                        if (oversized.length > 0) {
                                            alert('File berikut melebihi ukuran maksimal 3 MB:\n' + oversized.join('\n'));
                                            this.value = '';
                                            document.getElementById('fileCountText').textContent = 'Maks 10 file @ 3 MB';
                                        } else {
                                            document.getElementById('fileCountText').textContent = this.files.length + ' berkas dipilih (maks 3 MB/file)';
                                        }
                                    " />
                            </div>
                        </div>
                        <x-input-error class="mt-1.5" :messages="$errors->get('files')" />
                    </div>

                    <!-- Action Buttons di Bagian Bawah Formulir -->
                    <div class="pt-5 border-t border-gray-100 flex items-center justify-end gap-3">
                        <a href="{{ route('leave.index') }}"
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-700 font-semibold text-sm hover:bg-gray-50 transition shadow-sm">
                            Batal
                        </a>
                        <button type="submit" 
                            x-bind:disabled="isSubmitting || isLoading || isBackDate || hasConflict || !startDate || (leaveCategory === 'annual' && (isExceeded || deduction <= 0)) || (leaveCategory === 'special' && (specialDaysCount <= 0 || !selectedTypeId))"
                            class="inline-flex items-center justify-center gap-2 px-7 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm rounded-xl shadow-md shadow-blue-500/20 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-300">
                            <!-- Saat sedang submit permohonan ke backend -->
                            <span x-show="isSubmitting" class="inline-flex items-center gap-2" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                                <span>Mengajukan...</span>
                            </span>

                            <!-- Saat sedang kalkulasi sisa cuti (loading) -->
                            <span x-show="!isSubmitting && isLoading" class="inline-flex items-center gap-2" style="display: none;">
                                <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                                <span>Menghitung...</span>
                            </span>

                            <!-- Validasi lainnya -->
                            <span x-show="!isSubmitting && !isLoading && isBackDate" style="display: none;">Tanggal Back-date</span>
                            <span x-show="!isSubmitting && !isLoading && !isBackDate && hasConflict" style="display: none;">Tanggal Bentrok</span>
                            <span x-show="!isSubmitting && !isLoading && !isBackDate && !hasConflict" class="inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                                <span>{{ __('Ajukan Cuti') }}</span>
                            </span>
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <script>
        function leaveForm() {
            return {
                leaveCategory: 'annual', // 'annual' (default) atau 'special'
                isHalfDay: '0',
                halfDayType: 'first_half',
                startDate: '{{ old('start_date', '') }}',
                endDate: '{{ old('end_date', '') }}',
                minAllowedDate: '{{ \Carbon\Carbon::tomorrow()->toDateString() }}',
                currentRemaining: {{ $leaveBalance ? (float) $leaveBalance->remaining_leave : 0 }},
                deduction: 0.0,
                remainingAfter: {{ $leaveBalance ? (float) $leaveBalance->remaining_leave : 0 }},
                isExceeded: false,
                isBackDate: false,
                backDateMessage: '',
                hasConflict: false,
                conflicts: [],
                conflictMessage: '',
                workingDays: [],
                skippedDates: [],
                isLoading: false,
                isSubmitting: false,

                // Special Leave Data
                specialTypes: @json($specialTypesJson),
                selectedTypeId: '{{ old('special_leave_type_id', $specialLeaveTypes->first()?->id ?? '') }}',
                specialDaysCount: 0.0,
                specialEndDateFormatted: '',

                fpAnnualStart: null,
                fpAnnualEnd: null,
                fpSpecialStart: null,
                disabledDates: @json($disabledDates ?? []),

                get currentSpecialType() {
                    const idStr = String(this.selectedTypeId);
                    return this.specialTypes.find(t => String(t.id) === idStr) || this.specialTypes[0] || null;
                },

                get currentSpecialTypeName() {
                    return this.currentSpecialType ? this.currentSpecialType.name : 'Cuti Khusus';
                },

                get currentSpecialTypeDesc() {
                    return this.currentSpecialType ? this.currentSpecialType.description : '';
                },

                get currentSpecialMaxDays() {
                    return this.currentSpecialType ? Number(this.currentSpecialType.days_allowed) : 1;
                },

                init() {
                    this.$nextTick(() => {
                        this.initFlatpickr();
                        if (this.startDate) {
                            this.calculate();
                        }
                    });
                },

                initFlatpickr() {
                    const self = this;
                    if (typeof flatpickr === 'undefined') {
                        return;
                    }

                    const disabledList = Array.isArray(self.disabledDates) ? self.disabledDates : [];

                    try {
                        const startEl = document.getElementById('start_date');
                        const endEl = document.getElementById('end_date');
                        const specialStartEl = document.getElementById('special_start_date');

                        if (startEl) {
                            self.fpAnnualStart = flatpickr(startEl, {
                                disableMobile: true,
                                locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd F Y',
                                altInputClass: 'mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white cursor-pointer',
                                minDate: self.minAllowedDate,
                                disable: [
                                    ...disabledList,
                                    function(date) {
                                        if (self.leaveCategory === 'annual' && self.isHalfDay === '1') {
                                            return (date.getDay() === 0 || date.getDay() === 6);
                                        }
                                        return false;
                                    }
                                ],
                                defaultDate: self.startDate || null,
                                onChange: function(selectedDates, dateStr) {
                                    self.startDate = dateStr;
                                    self.onStartDateChange();
                                    if (self.fpAnnualEnd) {
                                        self.fpAnnualEnd.set('minDate', dateStr || self.minAllowedDate);
                                    }
                                    if (self.fpSpecialStart) {
                                        self.fpSpecialStart.setDate(dateStr, false);
                                    }
                                }
                            });
                        }

                        if (endEl) {
                            self.fpAnnualEnd = flatpickr(endEl, {
                                disableMobile: true,
                                locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd F Y',
                                altInputClass: 'mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white cursor-pointer',
                                minDate: self.startDate || self.minAllowedDate,
                                disable: disabledList,
                                defaultDate: self.endDate || null,
                                onChange: function(selectedDates, dateStr) {
                                    self.endDate = dateStr;
                                    self.onEndDateChange();
                                }
                            });
                        }

                        if (specialStartEl) {
                            self.fpSpecialStart = flatpickr(specialStartEl, {
                                disableMobile: true,
                                locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default',
                                dateFormat: 'Y-m-d',
                                altInput: true,
                                altFormat: 'd F Y',
                                altInputClass: 'mt-1.5 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm bg-white cursor-pointer',
                                minDate: self.minAllowedDate,
                                disable: disabledList,
                                defaultDate: self.startDate || null,
                                onChange: function(selectedDates, dateStr) {
                                    self.startDate = dateStr;
                                    self.onStartDateChange();
                                    if (self.fpAnnualStart) {
                                        self.fpAnnualStart.setDate(dateStr, false);
                                    }
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Flatpickr init error:', e);
                    }
                },

                onCategoryChange() {
                    if (this.leaveCategory === 'special' && !this.selectedTypeId && this.specialTypes.length > 0) {
                        this.selectedTypeId = this.specialTypes[0].id;
                    }
                    this.calculate();
                },

                onSpecialTypeSelected() {
                    this.calculate();
                },

                onTypeChange() {
                    if (this.isHalfDay === '1') {
                        this.endDate = this.startDate;
                        if (this.fpAnnualEnd) {
                            this.fpAnnualEnd.setDate(this.startDate, false);
                        }
                    }
                    if (this.fpAnnualStart) {
                        this.fpAnnualStart.redraw();
                    }
                    this.calculate();
                },

                onStartDateChange() {
                    if (this.leaveCategory === 'annual') {
                        if (this.isHalfDay === '1' || !this.endDate) {
                            this.endDate = this.startDate;
                            if (this.fpAnnualEnd) {
                                this.fpAnnualEnd.setDate(this.startDate, false);
                            }
                        }
                    }
                    this.calculate();
                },

                onEndDateChange() {
                    this.calculate();
                },

                useTemplateReason() {
                    const el = document.getElementById('reason');
                    if (!el) return;

                    if (this.leaveCategory === 'special') {
                        el.value = 'Mengajukan cuti khusus untuk keperluan ' + (this.currentSpecialTypeName || 'cuti khusus') + ' sesuai ketentuan yang berlaku.';
                    } else {
                        el.value = 'Mengajukan cuti tahunan untuk keperluan keluarga/pribadi.';
                    }
                },

                async calculate() {
                    if (!this.startDate) {
                        this.deduction = 0.0;
                        this.remainingAfter = this.currentRemaining;
                        this.specialDaysCount = 0.0;
                        this.specialEndDateFormatted = '';
                        this.isExceeded = false;
                        this.isBackDate = false;
                        this.hasConflict = false;
                        this.conflicts = [];
                        this.workingDays = [];
                        this.skippedDates = [];
                        return;
                    }

                    // Validasi cepat di sisi klien untuk back-date
                    if (this.startDate < this.minAllowedDate) {
                        this.isBackDate = true;
                        this.backDateMessage = 'Tanggal cuti tidak boleh tanggal lampau (back-date). Sesuai ketentuan, pengajuan paling lambat H-1 (mulai {{ \Carbon\Carbon::tomorrow()->format('d/m/Y') }}).';
                        this.deduction = 0.0;
                        this.remainingAfter = this.currentRemaining;
                        this.specialDaysCount = 0.0;
                        this.specialEndDateFormatted = '';
                        this.hasConflict = false;
                        this.conflicts = [];
                        this.workingDays = [];
                        this.skippedDates = [];
                        return;
                    } else {
                        this.isBackDate = false;
                        this.backDateMessage = '';
                    }

                    const targetEndDate = (this.isHalfDay === '1' || !this.endDate) ? this.startDate : this.endDate;

                    this.isLoading = true;
                    try {
                        const payload = {
                            _token: '{{ csrf_token() }}',
                            leave_category: this.leaveCategory,
                            start_date: this.startDate,
                        };

                        if (this.leaveCategory === 'special') {
                            payload.special_leave_type_id = this.selectedTypeId;
                        } else {
                            payload.is_half_day = this.isHalfDay === '1';
                            payload.end_date = targetEndDate;
                            payload.half_day_type = this.halfDayType;
                        }

                        const response = await fetch('{{ route('leave.calculate-preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (this.leaveCategory === 'special') {
                                this.specialDaysCount = Number(data.full_day_count || data.days_allowed || 0);
                                this.specialEndDateFormatted = data.end_date_formatted || '';
                                this.deduction = 0.0;
                                this.remainingAfter = this.currentRemaining;
                                this.isExceeded = false;
                            } else {
                                this.deduction = Number(data.total_deduction);
                                this.remainingAfter = Number(data.remaining_after);
                                this.isExceeded = Boolean(data.is_exceeded);
                            }

                            this.isBackDate = Boolean(data.is_backdate);
                            if (data.backdate_message) {
                                this.backDateMessage = data.backdate_message;
                            }
                            this.hasConflict = Boolean(data.has_conflict);
                            this.conflicts = data.conflicts || [];
                            this.conflictMessage = data.conflict_message || '';
                            this.workingDays = data.working_days || [];
                            this.skippedDates = data.skipped_dates || [];
                        }
                    } catch (err) {
                        console.error('Kalkulasi cuti gagal:', err);
                        // Fallback agar tombol tidak terblokir permanen jika network timeout
                        if (this.leaveCategory === 'special') {
                            this.specialDaysCount = this.currentSpecialMaxDays;
                        } else {
                            this.deduction = this.deduction > 0 ? this.deduction : 1;
                        }
                        this.isExceeded = false;
                        this.hasConflict = false;
                        this.isBackDate = false;
                    } finally {
                        this.isLoading = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
