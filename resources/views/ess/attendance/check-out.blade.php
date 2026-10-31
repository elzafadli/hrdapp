<x-app-layout>
    <div class="py-6" x-data="checkOutApp()">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-5">

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
                <div class="rounded-2xl p-5 bg-gradient-to-br from-rose-50/80 via-white to-amber-50/30 border border-rose-100/80 text-center space-y-1.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100/70 text-rose-800 text-xs font-semibold">
                        <i class="fa-regular fa-calendar-days text-[11px]"></i>
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>
                    <div class="text-4xl font-extrabold text-gray-900 tracking-wider font-mono pt-1" x-text="currentTime">
                        --:--:--
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

                <!-- 3. Info Lokasi Sekarang -->
                <div class="p-4 rounded-xl bg-gray-50/80 border border-gray-200/80 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-9 h-9 rounded-xl bg-rose-600 text-white flex items-center justify-center text-sm shadow-sm shrink-0">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="truncate">
                            <span class="text-[11px] text-gray-400 block font-medium">Lokasi Sekarang</span>
                            <span class="text-xs font-bold text-gray-900 truncate block" x-text="locationText">Mendeteksi lokasi...</span>
                            <span class="text-[10px] text-gray-500" x-show="coordsText" x-text="coordsText"></span>
                        </div>
                    </div>
                    <button type="button" @click="detectLocation()" 
                        class="px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 rounded-xl text-xs font-semibold shrink-0 transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-arrows-rotate text-[11px] text-rose-600" :class="{ 'fa-spin': isDetecting }"></i>
                        Perbarui
                    </button>
                </div>

                <!-- Status Sudah Check-Out -->
                @if($todayAttendance && $todayAttendance->check_out_time)
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3 text-xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg shrink-0"></i>
                        <div>
                            <span class="font-bold block">Sudah Presensi Keluar</span>
                            <span>Tercatat pada pukul <strong>{{ substr($todayAttendance->check_out_time, 0, 5) }} WITA</strong> (Durasi: {{ floor($todayAttendance->work_duration_minutes / 60) }} jam {{ $todayAttendance->work_duration_minutes % 60 }} menit).</span>
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
                @endif

                <!-- 4. Catatan Kepulangan Opsional -->
                <div class="space-y-1.5 pt-1">
                    <x-input-label for="notes" :value="__('Catatan Kepulangan (Opsional)')" />
                    <textarea id="notes" x-model="notes" rows="2"
                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 text-xs placeholder-gray-400"
                        placeholder="Tambahkan catatan kepulangan jika diperlukan..."></textarea>
                </div>

                <!-- 5. Tombol Aksi Presensi Keluar -->
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
                        <button type="button" @click="submitCheckOut()" :disabled="isSubmitting"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-sm rounded-xl shadow-md shadow-rose-500/20 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="isSubmitting" style="display: none;">
                                <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                                Menyimpan Kepulangan...
                            </span>
                            <span x-show="!isSubmitting">
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
                latitude: null,
                longitude: null,
                locationText: 'Mendeteksi lokasi...',
                coordsText: '',
                isDetecting: false,
                isSubmitting: false,
                notes: '',
                message: '',
                isSuccess: false,

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
                    this.currentTime = `${timeStr} WITA`;
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

                async submitCheckOut() {
                    if (this.isSubmitting) return;
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
                                notes: this.notes
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
