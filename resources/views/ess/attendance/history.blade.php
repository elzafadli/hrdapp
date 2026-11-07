<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('attendance.check-in') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Riwayat Presensi</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Catatan log kehadiran masuk & kepulangan Anda.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('attendance.check-in') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                        Presensi Masuk
                    </a>
                    <a href="{{ route('attendance.check-out') }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        Presensi Keluar
                    </a>
                </div>
            </div>

            <!-- Kartu Info Titik Lokasi & Radius Geolocation Aktif -->
            <div class="p-4 bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm border border-blue-100 shrink-0">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div>
                        <span class="text-[11px] text-gray-400 font-medium block">Titik Presensi Geolocation</span>
                        <span class="text-xs font-bold text-gray-800">{{ $officeSetting->office_name ?? 'Kantor Pusat' }}</span>
                        @if(isset($officeSetting->project) && $officeSetting->project)
                            <span class="text-[10px] text-blue-600 font-semibold block">• Proyek: {{ $officeSetting->project->name }}</span>
                        @elseif(isset($officeSetting->company) && $officeSetting->company)
                            <span class="text-[10px] text-gray-500 font-medium block">• {{ $officeSetting->company->name }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 sm:gap-6 text-xs text-gray-600">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center text-blue-600 text-[11px]">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block">Radius Toleransi</span>
                            <span class="font-bold text-gray-900">{{ $officeSetting->radius_meters ?? 100 }} Meter</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-gray-100 flex items-center justify-center {{ ($officeSetting->allow_outside_radius ?? true) ? 'text-amber-600' : 'text-emerald-600' }} text-[11px]">
                            <i class="fa-solid {{ ($officeSetting->allow_outside_radius ?? true) ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-400 block">Presensi Luar Radius</span>
                            <span class="font-bold text-gray-900">
                                {{ ($officeSetting->allow_outside_radius ?? true) ? 'Diizinkan (Catatan)' : 'Wajib Dalam Radius' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bulan & Tahun -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                <form action="{{ route('attendance.history') }}" method="GET" class="flex flex-wrap items-center gap-3">
                    <div class="w-44">
                        <x-select-input name="month" :options="[
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ]" :selected="$month" :noSelect2="true" />
                    </div>
                    <div class="w-32">
                        <x-select-input name="year" :options="[
                            date('Y') => date('Y'),
                            (date('Y') - 1) => (date('Y') - 1)
                        ]" :selected="$year" :noSelect2="true" />
                    </div>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
                        <i class="fa-solid fa-filter text-[11px] mr-1"></i> Filter
                    </button>
                </form>
            </div>

            @php
                $scheduleDaysMap = $schedule && $schedule->days ? $schedule->days->keyBy('day_of_week') : collect();
            @endphp

            <!-- Tabel Riwayat Kehadiran -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/75 text-gray-500 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3.5 whitespace-nowrap">Tanggal</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Jadwal Kerja</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Presensi Masuk</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Presensi Keluar</th>
                                <th class="px-6 py-3.5 text-center whitespace-nowrap">Durasi Kerja</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Status Masuk</th>
                                <th class="px-6 py-3.5 whitespace-nowrap">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            @forelse($attendances as $att)
                                @php
                                    $dayOfWeek = $att->date ? $att->date->dayOfWeekIso : null;
                                    $daySchedule = $dayOfWeek ? $scheduleDaysMap->get($dayOfWeek) : null;
                                    $isWork = $daySchedule ? (bool) $daySchedule->is_work_day : !in_array($dayOfWeek, [6, 7]);
                                    $start = $daySchedule && $daySchedule->start_time ? substr($daySchedule->start_time, 0, 5) : '08:00';
                                    $end = $daySchedule && $daySchedule->end_time ? substr($daySchedule->end_time, 0, 5) : '17:00';
                                    $isHoliday = $att->date ? \App\Models\Holiday::isHoliday($att->date) : false;
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 font-semibold text-gray-900 text-xs whitespace-nowrap">
                                        {{ $att->date?->translatedFormat('l, d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-xs whitespace-nowrap">
                                        @if($isHoliday)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                                <i class="fa-regular fa-calendar-xmark text-[10px]"></i> Libur Nasional
                                            </span>
                                        @elseif($isWork)
                                            <div class="space-y-0.5">
                                                <span class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-gray-800 bg-gray-100/80 px-2 py-0.5 rounded-md border border-gray-200">
                                                    <i class="fa-regular fa-clock text-blue-500 text-[10px]"></i>
                                                    {{ $start }} - {{ $end }}
                                                </span>
                                                <div class="text-[10px] text-amber-700 font-medium">
                                                    Tol: s/d {{ $daySchedule && $daySchedule->late_tolerance_time ? substr($daySchedule->late_tolerance_time, 0, 5) : '08:30' }}
                                                </div>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                                Day Off
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs whitespace-nowrap">
                                        @if($att->check_in_time)
                                            <div class="flex items-center gap-2.5">
                                                @if($att->check_in_photo_url)
                                                    <a href="{{ $att->check_in_photo_url }}" target="_blank" class="shrink-0" title="Lihat foto selfie presensi masuk">
                                                        <img src="{{ $att->check_in_photo_url }}" alt="Selfie Masuk" class="w-8 h-8 rounded-lg object-cover border border-blue-200 shadow-xs hover:scale-105 transition">
                                                    </a>
                                                @endif
                                                <div>
                                                    <span class="font-bold text-gray-800">{{ substr($att->check_in_time, 0, 5) }}</span> WITA
                                                    @if($att->check_in_distance !== null)
                                                        <div class="text-[10px] text-gray-500 flex items-center gap-1 mt-0.5" title="Jarak GPS saat presensi masuk">
                                                            <i class="fa-solid fa-location-crosshairs {{ ($officeSetting && $att->check_in_distance > $officeSetting->radius_meters) ? 'text-amber-500' : 'text-emerald-500' }}"></i>
                                                            <span>{{ number_format($att->check_in_distance, 0) }} m</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs whitespace-nowrap">
                                        @if($att->check_out_time)
                                            <div class="flex items-center gap-2.5">
                                                @if($att->check_out_photo_url)
                                                    <a href="{{ $att->check_out_photo_url }}" target="_blank" class="shrink-0" title="Lihat foto selfie presensi keluar">
                                                        <img src="{{ $att->check_out_photo_url }}" alt="Selfie Keluar" class="w-8 h-8 rounded-lg object-cover border border-rose-200 shadow-xs hover:scale-105 transition">
                                                    </a>
                                                @endif
                                                <div>
                                                    <span class="font-bold text-gray-800">{{ substr($att->check_out_time, 0, 5) }}</span> WITA
                                                    @if($att->check_out_distance !== null)
                                                        <div class="text-[10px] text-gray-500 flex items-center gap-1 mt-0.5" title="Jarak GPS saat presensi keluar">
                                                            <i class="fa-solid fa-location-crosshairs {{ ($officeSetting && $att->check_out_distance > $officeSetting->radius_meters) ? 'text-amber-500' : 'text-emerald-500' }}"></i>
                                                            <span>{{ number_format($att->check_out_distance, 0) }} m</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs whitespace-nowrap">
                                        @if($att->work_duration_minutes)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                                {{ floor($att->work_duration_minutes / 60) }}j {{ $att->work_duration_minutes % 60 }}m
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($att->check_in_status === 'on_time')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> Tepat Waktu
                                            </span>
                                        @elseif($att->check_in_status === 'late')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                <i class="fa-solid fa-clock text-[10px]"></i> Terlambat
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 max-w-xs truncate">
                                        {{ $att->check_in_notes ?? $att->check_out_notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="fa-solid fa-fingerprint text-3xl text-gray-300"></i>
                                            <p class="text-sm">Belum ada catatan presensi pada bulan ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($attendances->hasPages())
                    <div class="p-4 border-t border-gray-100">
                        {{ $attendances->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
