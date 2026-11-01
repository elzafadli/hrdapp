<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs rounded-2xl border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-lg text-gray-900 leading-tight">
                            {{ $schedule->exists ? 'Edit Pola Jadwal Kerja' : 'Buat Pola Jadwal Kerja Baru' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Tentukan jam masuk, jam pulang, dan hari kerja operasional (Senin s/d Minggu).</p>
                    </div>
                    <a href="{{ route('work-schedules.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Kembali
                    </a>
                </div>

                <form method="POST"
                    action="{{ $schedule->exists ? route('work-schedules.update', $schedule) : route('work-schedules.store') }}">
                    @csrf
                    @if($schedule->exists)
                        @method('PUT')
                    @endif

                    <div class="p-6 text-gray-900 space-y-6">
                        @if ($errors->any())
                            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-xs" role="alert">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fa-solid fa-circle-xmark text-red-600"></i>
                                    <span class="font-semibold text-xs">Periksa kembali data input:</span>
                                </div>
                                <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Profil Jadwal -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div class="md:col-span-2">
                                <x-input-label for="name" :value="__('Nama Jadwal Kerja')" :required="true" />
                                <x-text-input id="name" name="name" type="text" placeholder="Contoh: Reguler 5 Hari (08:00 - 17:00)" class="mt-1 block w-full"
                                    :value="old('name', $schedule->name)" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="code" :value="__('Kode Singkat / Shift')" :required="true" />
                                <x-text-input id="code" name="code" type="text" placeholder="Contoh: REGULER_5D, SHIFT_PAGI" class="mt-1 block w-full uppercase"
                                    :value="old('code', $schedule->code)" required />
                                <x-input-error class="mt-1.5" :messages="$errors->get('code')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Keterangan / Aturan Jadwal')" />
                            <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs text-xs"
                                placeholder="Keterangan toleransi keterlambatan, shift karyawan, dll.">{{ old('description', $schedule->description) }}</textarea>
                            <x-input-error class="mt-1.5" :messages="$errors->get('description')" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_default" name="is_default" value="1"
                                {{ old('is_default', $schedule->is_default) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 w-4 h-4">
                            <label for="is_default" class="text-xs font-semibold text-gray-700 cursor-pointer">
                                Jadikan sebagai jadwal default sistem (berlaku otomatis untuk seluruh karyawan baru)
                            </label>
                        </div>

                        <!-- 7-Day Matrix Settings -->
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900">Matriks 7 Hari Operasional (Senin - Minggu)</h3>
                                    <p class="text-xs text-gray-500">Centang "Hari Kerja" untuk mengaktifkan jam masuk dan jam pulang pada hari tersebut.</p>
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 font-semibold text-gray-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left w-36">Hari</th>
                                            <th class="px-4 py-3 text-center w-36">Status Hari Kerja</th>
                                            <th class="px-4 py-3 text-left">Jam Masuk (Format 24 Jam)</th>
                                            <th class="px-4 py-3 text-left">Jam Pulang (Format 24 Jam)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($dayNames as $dayNum => $dayLabel)
                                            @php
                                                $dayData = $scheduleDays[$dayNum] ?? null;
                                                $isWorkDay = old("days.{$dayNum}.is_work_day", $dayData ? $dayData->is_work_day : ($dayNum <= 5));
                                                $startTime = old("days.{$dayNum}.start_time", $dayData ? substr($dayData->start_time, 0, 5) : '08:00');
                                                $endTime = old("days.{$dayNum}.end_time", $dayData ? substr($dayData->end_time, 0, 5) : '17:00');
                                            @endphp
                                            <tr class="hover:bg-gray-50/70 transition" id="row-day-{{ $dayNum }}">
                                                <td class="px-4 py-3 font-bold text-gray-800">
                                                    {{ $dayLabel }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" name="days[{{ $dayNum }}][is_work_day]" value="1"
                                                            id="work_day_{{ $dayNum }}"
                                                            onchange="toggleDayInputs({{ $dayNum }})"
                                                            {{ $isWorkDay ? 'checked' : '' }}
                                                            class="sr-only peer">
                                                        <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                                        <span class="ms-2 text-xs font-medium text-gray-700" id="label_work_day_{{ $dayNum }}">
                                                            {{ $isWorkDay ? 'Kerja' : 'Libur (OFF)' }}
                                                        </span>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <x-text-input type="time" name="days[{{ $dayNum }}][start_time]" id="start_time_{{ $dayNum }}"
                                                        :value="$startTime" class="w-40 {{ $isWorkDay ? '' : 'bg-gray-100 text-gray-400' }}"
                                                        :disabled="!$isWorkDay" />
                                                </td>
                                                <td class="px-4 py-3">
                                                    <x-text-input type="time" name="days[{{ $dayNum }}][end_time]" id="end_time_{{ $dayNum }}"
                                                        :value="$endTime" class="w-40 {{ $isWorkDay ? '' : 'bg-gray-100 text-gray-400' }}"
                                                        :disabled="!$isWorkDay" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-5 border-t border-gray-100 flex items-center justify-end gap-3">
                            <a href="{{ route('work-schedules.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-xs hover:bg-gray-50 transition">
                                Batal
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-xs uppercase tracking-widest shadow-xs transition">
                                Simpan Jadwal Kerja
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleDayInputs(dayNum) {
                const isChecked = document.getElementById('work_day_' + dayNum).checked;
                const startInput = document.getElementById('start_time_' + dayNum);
                const endInput = document.getElementById('end_time_' + dayNum);
                const label = document.getElementById('label_work_day_' + dayNum);

                if (isChecked) {
                    startInput.disabled = false;
                    endInput.disabled = false;
                    startInput.classList.remove('bg-gray-100', 'text-gray-400');
                    endInput.classList.remove('bg-gray-100', 'text-gray-400');
                    label.textContent = 'Kerja';
                } else {
                    startInput.disabled = true;
                    endInput.disabled = true;
                    startInput.classList.add('bg-gray-100', 'text-gray-400');
                    endInput.classList.add('bg-gray-100', 'text-gray-400');
                    label.textContent = 'Libur (OFF)';
                }
            }
        </script>
    @endpush
</x-app-layout>

