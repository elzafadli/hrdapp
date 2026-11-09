<x-app-layout>
    <div class="py-6" x-data="checkOutApp()">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            <!-- Top Bar Navigasi -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Presensi Keluar</h2>
                        <p class="text-xs text-gray-400">Catat waktu kepulangan kerja harian Anda</p>
                    </div>
                </div>

                <a href="{{ route('attendance.history') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                    Riwayat
                </a>
            </div>

            <!-- Notifikasi Pesan Sukses / Error -->
            <div x-show="message" 
                 x-transition
                 :class="isSuccess ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'"
                 class="p-4 rounded-xl border text-sm flex items-start gap-3 shadow-sm"
                 style="display: none;">
                <i :class="isSuccess ? 'fa-solid fa-circle-check text-emerald-500' : 'fa-solid fa-circle-exclamation text-rose-500'" class="text-lg mt-0.5 shrink-0"></i>
                <div class="flex-1 font-medium text-xs leading-relaxed" x-text="message"></div>
                <button type="button" @click="message = ''" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Kartu Utama Presensi Keluar (White ESS Golden Style) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-5">
                
                <!-- 1. Real-Time Jam & Tanggal -->
                <div class="rounded-2xl p-4 sm:p-5 bg-gradient-to-br from-rose-50/80 via-white to-amber-50/30 border border-rose-100/80 text-center space-y-1.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100/70 text-rose-800 text-xs font-semibold">
                        <i class="fa-regular fa-calendar-days text-[11px]"></i>
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>
                    <div class="flex items-baseline justify-center gap-1.5 pt-1">
                        <span class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight font-mono" x-text="timeOnly">
                            --:--:--
                        </span>
                        <span class="text-xs sm:text-sm font-bold text-rose-600 bg-rose-100/80 px-2 py-0.5 rounded-md font-mono" x-text="timeZone">
                            WITA
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ $employee->name ?? auth()->user()->name }} 
                        @if($employee && $employee->jabatan)
                            · <span class="font-medium text-gray-700">{{ $employee->jabatan->jabatan }}</span>
                        @endif
                    </p>
                </div>

                <!-- 2. Ringkasan Presensi Hari Ini (Jam Masuk & Durasi) -->
                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="text-[11px] text-gray-400 block font-medium">Jam Masuk</span>
                        <span class="text-sm font-bold text-gray-800 mt-0.5 block">
                            @if($todayAttendance && $todayAttendance->check_in_time)
                                {{ substr($todayAttendance->check_in_time, 0, 5) }} WITA
                            @else
                                <span class="text-rose-500 font-semibold">Belum Masuk</span>
                            @endif
                        </span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-blue-50/60 border border-blue-100">
                        <span class="text-[11px] text-blue-600 block font-medium">Shift Selesai</span>
                        <span class="text-sm font-bold text-blue-800 mt-0.5 block">
                            {{ $shift['end_time'] }} WITA
                        </span>
                    </div>
                </div>

                <!-- 3. Info Lokasi & Radius Geolocation -->
                <div class="space-y-3">
                    <!-- Lokasi GPS Sekarang -->
                    <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50/80 border border-gray-200/80 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5 sm:gap-3 min-w-0 flex-1">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm shadow-2xs shrink-0 mt-0.5">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-[11px] text-gray-400 block font-medium">Lokasi GPS Anda</span>
                                <span class="text-xs font-bold text-gray-900 break-words leading-relaxed block" x-text="locationText">Mendeteksi lokasi...</span>
                                <span class="text-[10px] text-gray-500 font-mono break-all block mt-0.5" x-show="coordsText" x-text="coordsText"></span>
                            </div>
                        </div>
                        <button type="button" @click="detectLocation()" 
                            class="px-2.5 sm:px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold shrink-0 transition flex items-center gap-1.5 shadow-2xs mt-0.5 cursor-pointer">
                            <i class="fa-solid fa-arrows-rotate text-[11px] text-rose-600" :class="{ 'fa-spin': isDetecting }"></i>
                            <span>Perbarui</span>
                        </button>
                    </div>

                    <!-- Target Titik Kantor & Radius Geolocation -->
                    <div class="p-3.5 sm:p-4 rounded-xl bg-gray-50/90 border border-gray-200/80 space-y-3">
                        <div class="flex items-start sm:items-center justify-between gap-2 border-b border-gray-200/70 pb-2.5 flex-wrap sm:flex-nowrap">
                            <div class="flex items-start gap-2 min-w-0 flex-1">
                                <i class="fa-solid fa-building text-rose-600 text-xs mt-1 shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <span class="text-xs font-bold text-gray-800 break-words block">{{ $officeSetting->office_name ?? 'Kantor Pusat' }}</span>
                                    @if(isset($officeSetting->project) && $officeSetting->project)
                                        <span class="text-[10px] text-rose-600 font-semibold break-words block">• Proyek: {{ $officeSetting->project->name }}</span>
                                    @elseif(isset($officeSetting->company) && $officeSetting->company)
                                        <span class="text-[10px] text-gray-500 font-medium break-words block">• {{ $officeSetting->company->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 font-medium shrink-0 ml-auto sm:ml-0">
                                <i class="fa-solid fa-bullseye text-rose-600"></i>
                                <span>Radius: <strong class="text-gray-800">{{ $officeSetting->radius_meters ?? 100 }} m</strong></span>
                            </div>
                        </div>

                        <!-- Status Validasi Radius Real-Time -->
                        <div>
                            <!-- Sedang Mencari/Menghitung Jarak -->
                            <div x-show="distance === null" class="flex items-center gap-2 text-xs text-gray-500 py-1">
                                <i class="fa-solid fa-circle-notch fa-spin text-rose-500 text-xs"></i>
                                <span>Menghitung jarak ke titik presensi kantor...</span>
                            </div>

                            <!-- Dalam Radius Presensi -->
                            <div x-show="distance !== null && isWithinRadius" style="display: none;"
                                class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-2.5 text-xs">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5 shrink-0"></i>
                                <div>
                                    <span class="font-bold block">Dalam Radius Presensi</span>
                                    <span class="text-[11px] text-emerald-700">
                                        Jarak Anda <strong x-text="formattedDistance"></strong> dari {{ $officeSetting->office_name ?? 'Kantor' }} (Maks. {{ $officeSetting->radius_meters ?? 100 }} m). Presensi diizinkan.
                                    </span>
                                </div>
                            </div>

                            <!-- Di Luar Radius (Toleransi Catatan) -->
                            <div x-show="distance !== null && !isWithinRadius && allowOutsideRadius" style="display: none;"
                                class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-start gap-2.5 text-xs">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base mt-0.5 shrink-0"></i>
                                <div>
                                    <span class="font-bold block">Di Luar Batas Radius</span>
                                    <span class="text-[11px] text-amber-700">
                                        Jarak Anda <strong x-text="formattedDistance"></strong> dari kantor (Maks. {{ $officeSetting->radius_meters ?? 100 }} m). Presensi diizinkan dengan toleransi, pastikan mengisi alasan pada catatan.
                                    </span>
                                </div>
                            </div>

                            <!-- Di Luar Radius (Dilarang) -->
                            <div x-show="distance !== null && !isWithinRadius && !allowOutsideRadius" style="display: none;"
                                class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-2.5 text-xs">
                                <i class="fa-solid fa-circle-xmark text-rose-600 text-base mt-0.5 shrink-0"></i>
                                <div>
                                    <span class="font-bold block">Di Luar Batas Radius Presensi</span>
                                    <span class="text-[11px] text-rose-700">
                                        Jarak Anda <strong x-text="formattedDistance"></strong> dari kantor. Presensi hanya diizinkan dalam radius maksimal {{ $officeSetting->radius_meters ?? 100 }} meter dari {{ $officeSetting->office_name ?? 'Kantor' }}.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Sudah Check-Out -->
                @if($todayAttendance && $todayAttendance->check_out_time)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3.5 text-xs">
                        @if($todayAttendance->check_out_photo_url)
                            <img src="{{ $todayAttendance->check_out_photo_url }}" alt="Foto Presensi Keluar" class="w-14 h-14 rounded-xl object-cover shrink-0 border border-emerald-300 shadow-xs">
                        @else
                            <i class="fa-solid fa-circle-check text-emerald-600 text-lg shrink-0 mt-0.5"></i>
                        @endif
                        <div class="space-y-1">
                            <span class="font-bold block">Sudah Presensi Keluar Hari Ini</span>
                            <span>Tercatat pada pukul <strong>{{ substr($todayAttendance->check_out_time, 0, 5) }} WITA</strong> (Durasi: {{ floor($todayAttendance->work_duration_minutes / 60) }} jam {{ $todayAttendance->work_duration_minutes % 60 }} menit).</span>
                            @if($todayAttendance->check_out_distance !== null)
                                <div class="pt-1.5 border-t border-emerald-200/60 text-[11px] text-emerald-700 flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-crosshairs text-emerald-600"></i>
                                    <span>Jarak saat kepulangan: <strong>{{ number_format($todayAttendance->check_out_distance, 0) }} meter</strong> dari {{ $officeSetting->office_name ?? 'Kantor' }}.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Peringatan Jika Belum Presensi Masuk -->
                @if(!$todayAttendance || !$todayAttendance->check_in_time)
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 flex items-center gap-3 text-xs">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg shrink-0"></i>
                        <div>
                            <span class="font-bold block">Belum Presensi Masuk</span>
                            <span>Anda belum melakukan presensi masuk hari ini. Silakan <a href="{{ route('attendance.check-in') }}" class="font-bold underline hover:text-amber-900">presensi masuk terlebih dahulu</a>.</span>
                        </div>
                    </div>
                @elseif(!$todayAttendance->check_out_time)
                    <!-- 4. Foto Selfie Presensi Pulang (Wajib Live Kamera) -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                <i class="fa-solid fa-camera text-rose-600"></i>
                                Foto Selfie Kepulangan <span class="text-rose-500 font-bold">*Wajib Kamera</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Live Camera</span>
                        </div>

                        <!-- Viewport Kamera / Hasil Foto -->
                        <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/70 p-3 overflow-hidden">
                            
                            <!-- Banner Peringatan / Error Kamera -->
                            <div x-show="cameraError" x-transition class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-2.5" style="display: none;">
                                <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5 text-sm shrink-0"></i>
                                <div class="flex-1 leading-relaxed">
                                    <span class="font-bold block">Kendala Akses Kamera</span>
                                    <span x-text="cameraError"></span>
                                </div>
                                <button type="button" @click="cameraError = ''" class="text-rose-400 hover:text-rose-600">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <!-- 1. Kondisi Kamera Belum Dibuka & Belum Ada Foto -->
                            <div x-show="!cameraActive && !photoData" class="py-6 text-center space-y-3">
                                <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl shadow-xs">
                                    <i class="fa-solid fa-camera"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs font-bold text-gray-800">Ambil Foto Selfie Kepulangan</p>
                                    <p class="text-[11px] text-gray-500 max-w-xs mx-auto">
                                        Foto wajib diambil langsung secara real-time dari kamera perangkat Anda.
                                    </p>
                                </div>
                                <div class="flex items-center justify-center pt-1">
                                    <button type="button" @click="startCamera()"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                                        <i class="fa-solid fa-camera text-sm"></i>
                                        Nyalakan Kamera
                                    </button>
                                </div>
                            </div>

                            <!-- 2. Kondisi Kamera Aktif (Live Video Viewfinder) -->
                            <div x-show="cameraActive" style="display: none;" class="space-y-3">
                                <div class="relative rounded-xl overflow-hidden bg-black aspect-video sm:aspect-[4/3] flex items-center justify-center shadow-inner">
                                    <video id="cameraVideo" autoplay playsinline muted class="w-full h-full object-cover -scale-x-100"></video>
                                    
                                    <!-- Guide Oval Selfie -->
                                    <div class="absolute inset-0 pointer-events-none border-2 border-white/20 rounded-xl flex items-center justify-center">
                                        <div class="w-36 h-48 sm:w-44 sm:h-56 rounded-[50%] border-2 border-dashed border-white/60"></div>
                                    </div>

                                    <!-- Tombol Tutup Kamera -->
                                    <button type="button" @click="stopCamera()" class="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white hover:bg-black/80 flex items-center justify-center text-xs transition">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <div class="flex items-center justify-center">
                                    <button type="button" @click="capturePhoto()"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                                        <i class="fa-solid fa-camera text-sm"></i>
                                        Ambil Foto (Snap)
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Kondisi Foto Sudah Diambil (Preview Foto) -->
                            <div x-show="photoData" style="display: none;" class="space-y-2">
                                <div class="relative rounded-xl overflow-hidden border border-emerald-200 bg-white p-1 shadow-xs">
                                    <img :src="photoData" alt="Preview Selfie Kepulangan" class="w-full max-h-64 object-cover rounded-lg mx-auto">
                                </div>
                                <div class="flex items-center justify-between px-1">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700">
                                        <i class="fa-solid fa-circle-check text-emerald-600"></i> Foto Selfie Siap
                                    </span>
                                    <button type="button" @click="retakePhoto()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold shadow-xs transition">
                                        <i class="fa-solid fa-arrows-rotate text-rose-600 text-xs"></i> Foto Ulang
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

                <!-- 5. Catatan Kepulangan Opsional -->
                <div class="space-y-1.5 pt-1">
                    <x-input-label for="notes" :value="__('Catatan Kepulangan (Opsional)')" />
                    <textarea id="notes" x-model="notes" rows="2"
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 text-xs placeholder-gray-400"
                        placeholder="Tambahkan catatan kepulangan jika diperlukan..."></textarea>
                </div>

                <!-- 6. Tombol Aksi Presensi Keluar -->
                <div class="pt-3 border-t border-gray-100">
                    @if($todayAttendance && $todayAttendance->check_out_time)
                        <button type="button" disabled
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-emerald-100 text-emerald-700 font-bold text-sm rounded-xl cursor-not-allowed">
                            <i class="fa-solid fa-check text-sm"></i>
                            Presensi Keluar Hari Ini Selesai
                        </button>
                    @elseif(!$todayAttendance || !$todayAttendance->check_in_time)
                        <button type="button" disabled
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gray-200 text-gray-500 font-bold text-sm rounded-xl cursor-not-allowed">
                            <i class="fa-solid fa-lock text-sm"></i>
                            Presensi Masuk Terlebih Dahulu
                        </button>
                    @else
                        <button type="button" @click="submitCheckOut()" 
                            :disabled="isSubmitting || !photoData || (!allowOutsideRadius && distance !== null && !isWithinRadius)"
                            :class="(!photoData || (!allowOutsideRadius && distance !== null && !isWithinRadius)) ? 'bg-gray-200 text-gray-500 cursor-not-allowed shadow-none' : 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-md shadow-rose-500/20'"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 font-bold text-sm rounded-xl transition disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                Menyimpan Kepulangan...
                            </span>
                            <span x-show="!isSubmitting && (!allowOutsideRadius && distance !== null && !isWithinRadius)" style="display: none;">
                                <i class="fa-solid fa-ban text-sm mr-1"></i>
                                Di Luar Radius Presensi
                            </span>
                            <span x-show="!isSubmitting && !photoData && (allowOutsideRadius || isWithinRadius || distance === null)" style="display: none;">
                                <i class="fa-solid fa-camera text-sm mr-1"></i>
                                Ambil Foto Selfie Terlebih Dahulu
                            </span>
                            <span x-show="!isSubmitting && photoData && (allowOutsideRadius || isWithinRadius || distance === null)">
                                <i class="fa-solid fa-arrow-right-from-bracket text-sm mr-1"></i>
                                Presensi Keluar Sekarang
                            </span>
                        </button>
                    @endif
                </div>

            </div>

        </div>
    </div>

    <script>
        function checkOutApp() {
            return {
                currentTime: '--:--:--',
                timeOnly: '--:--:--',
                timeZone: 'WITA',
                latitude: null,
                longitude: null,
                locationText: 'Mendeteksi lokasi...',
                coordsText: '',
                isDetecting: false,
                isSubmitting: false,
                notes: '',
                message: '',
                isSuccess: false,

                // Kamera & Foto Selfie
                cameraActive: false,
                cameraError: '',
                photoData: null,
                mediaStream: null,

                // Setting Titik Kantor & Radius Geolocation
                officeName: @json($officeSetting->office_name ?? 'Kantor Pusat'),
                officeLat: {{ (float) ($officeSetting->latitude ?? 0) }},
                officeLng: {{ (float) ($officeSetting->longitude ?? 0) }},
                officeRadius: {{ (int) ($officeSetting->radius_meters ?? 100) }},
                allowOutsideRadius: {{ ($officeSetting->allow_outside_radius ?? true) ? 'true' : 'false' }},
                distance: null,
                formattedDistance: '',
                isWithinRadius: null,

                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                    this.detectLocation();
                },

                updateTime() {
                    const now = new Date();
                    const timeStr = new Intl.DateTimeFormat('en-GB', {
                        timeZone: 'Asia/Makassar',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false
                    }).format(now);
                    this.timeOnly = timeStr;
                    this.timeZone = 'WITA';
                    this.currentTime = `${timeStr} WITA`;
                },

                calculateDistance(lat1, lon1, lat2, lon2) {
                    if (!lat1 || !lon1 || !lat2 || !lon2) return null;
                    const R = 6371000; // Radius bumi dalam meter
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                              Math.sin(dLon / 2) * Math.sin(dLon / 2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                    return Math.round(R * c);
                },

                updateRadiusStatus() {
                    if (this.latitude && this.longitude && this.officeLat && this.officeLng) {
                        this.distance = this.calculateDistance(this.latitude, this.longitude, this.officeLat, this.officeLng);
                        if (this.distance < 1000) {
                            this.formattedDistance = `${this.distance} meter`;
                        } else {
                            this.formattedDistance = `${(this.distance / 1000).toFixed(2)} km`;
                        }
                        this.isWithinRadius = this.distance <= this.officeRadius;
                    } else {
                        this.distance = null;
                        this.formattedDistance = '';
                        this.isWithinRadius = null;
                    }
                },

                detectLocation() {
                    this.isDetecting = true;
                    this.locationText = 'Mencari sinyal GPS...';

                    if (!navigator.geolocation) {
                        this.locationText = 'GPS tidak didukung perangkat ini';
                        this.isDetecting = false;
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            this.latitude = position.coords.latitude;
                            this.longitude = position.coords.longitude;
                            this.coordsText = `Koordinat: ${this.latitude.toFixed(5)}, ${this.longitude.toFixed(5)}`;
                            this.updateRadiusStatus();
                            this.reverseGeocode(this.latitude, this.longitude);
                            this.isDetecting = false;
                        },
                        (error) => {
                            console.warn('Geolocation error:', error);
                            this.locationText = 'Lokasi aktif (koordinat default)';
                            this.isDetecting = false;
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                async reverseGeocode(lat, lon) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`, {
                            headers: { 'Accept-Language': 'id' }
                        });
                        const data = await res.json();
                        if (data && data.display_name) {
                            const parts = data.display_name.split(',');
                            this.locationText = parts.slice(0, 3).join(',').trim();
                        } else {
                            this.locationText = 'Lokasi GPS Terdeteksi';
                        }
                    } catch (e) {
                        this.locationText = 'Lokasi GPS Terdeteksi';
                    }
                },

                // Logika Kamera & Selfie
                startCamera() {
                    this.cameraError = '';

                    // Periksa Secure Context (Kamera browser membutuhkan HTTPS atau localhost)
                    if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                        this.cameraError = 'Akses kamera browser memerlukan koneksi aman HTTPS atau localhost.';
                        return;
                    }

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        this.cameraError = 'Browser perangkat Anda tidak mendukung akses kamera langsung (getUserMedia).';
                        return;
                    }

                    // 1. Coba buka dengan facingMode ideal 'user' (selfie camera)
                    const constraints = {
                        video: {
                            facingMode: { ideal: 'user' },
                            width: { ideal: 640 },
                            height: { ideal: 480 }
                        },
                        audio: false
                    };

                    navigator.mediaDevices.getUserMedia(constraints)
                    .then((stream) => {
                        this.attachCameraStream(stream);
                    })
                    .catch((err) => {
                        console.warn('Gagal membuka kamera selfie spesifik, mencoba kamera default...', err);
                        // 2. Fallback: buka sembarang video camera (mendukung webcam laptop/PC & USB camera)
                        navigator.mediaDevices.getUserMedia({ video: true, audio: false })
                        .then((stream) => {
                            this.attachCameraStream(stream);
                        })
                        .catch((fallbackErr) => {
                            console.error('Kamera gagal dibuka:', fallbackErr);
                            let errMsg = 'Tidak dapat mengakses kamera perangkat.';
                            if (fallbackErr.name === 'NotAllowedError' || fallbackErr.name === 'PermissionDeniedError') {
                                errMsg = 'Izin kamera ditolak. Harap izinkan akses kamera di pengaturan browser/perangkat Anda.';
                            } else if (fallbackErr.name === 'NotFoundError' || fallbackErr.name === 'DevicesNotFoundError') {
                                errMsg = 'Kamera tidak terdeteksi pada perangkat Anda.';
                            } else if (fallbackErr.name === 'NotReadableError' || fallbackErr.name === 'TrackStartError') {
                                errMsg = 'Kamera sedang digunakan oleh aplikasi lain.';
                            }
                            this.cameraError = errMsg;
                        });
                    });
                },

                attachCameraStream(stream) {
                    this.mediaStream = stream;
                    this.cameraActive = true;
                    this.cameraError = '';
                    this.$nextTick(() => {
                        const video = document.getElementById('cameraVideo');
                        if (video) {
                            video.srcObject = stream;
                            video.play().catch(e => console.warn('Video play error:', e));
                        }
                    });
                },

                stopCamera() {
                    if (this.mediaStream) {
                        this.mediaStream.getTracks().forEach(track => track.stop());
                        this.mediaStream = null;
                    }
                    this.cameraActive = false;
                },

                capturePhoto() {
                    const video = document.getElementById('cameraVideo');
                    if (!video) return;

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');

                    // Mirror horizontal agar sesuai tampilan selfie
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    this.photoData = canvas.toDataURL('image/jpeg', 0.85);
                    this.stopCamera();
                },

                retakePhoto() {
                    this.photoData = null;
                    this.startCamera();
                },

                async submitCheckOut() {
                    if (this.isSubmitting) return;

                    // Validasi wajib foto kamera
                    if (!this.photoData) {
                        this.isSuccess = false;
                        this.message = 'Foto selfie kepulangan wajib diambil langsung melalui kamera HP / webcam.';
                        return;
                    }

                    // Validasi radius sebelum submit
                    if (!this.allowOutsideRadius && this.distance !== null && !this.isWithinRadius) {
                        this.isSuccess = false;
                        this.message = `Presensi gagal. Posisi Anda berada di luar radius presensi (${this.formattedDistance} dari ${this.officeName}, batas maksimal ${this.officeRadius} meter).`;
                        return;
                    }

                    this.isSubmitting = true;
                    this.message = '';

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch("{{ route('attendance.check-out.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                latitude: this.latitude,
                                longitude: this.longitude,
                                notes: this.notes,
                                photo: this.photoData
                            })
                        });

                        const res = await response.json();

                        if (response.ok && res.success) {
                            this.isSuccess = true;
                            this.message = res.message;
                            setTimeout(() => {
                                window.location.reload();
                            }, 1200);
                        } else {
                            this.isSuccess = false;
                            this.message = res.message || 'Gagal menyimpan presensi keluar.';
                        }
                    } catch (e) {
                        this.isSuccess = false;
                        this.message = 'Terjadi kesalahan jaringan atau server saat menyimpan presensi keluar.';
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>
